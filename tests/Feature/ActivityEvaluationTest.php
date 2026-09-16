<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\ActivityImport;
use App\Models\Beneficiary;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\ProgramObjective;
use App\Models\User;
use App\Services\ActivityEvaluationTemplate;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

/**
 * v4.13 step 6: per-activity evaluation scores are imported and aggregated
 * (mean) into the activity's single columns (blueprint 5.6, D13, 6.19).
 */
class ActivityEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBase(array $activityOverrides = []): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0009']);

        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-010',
            'title' => 'Evaluation Import Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'program_lead_id' => $faculty->id,
        ]);

        $activity = Activity::create(array_merge([
            'extension_program_id' => $program->id,
            'title' => 'Skills Training',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'completed',
        ], $activityOverrides));

        $roster = collect();
        foreach ([['Juan', 'Aguilar'], ['Maria', 'Bautista'], ['Pedro', 'Cruz']] as [$first, $last]) {
            $beneficiary = Beneficiary::create([
                'first_name' => $first, 'last_name' => $last,
                'gender' => 'Female', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
            ]);
            $program->beneficiaries()->syncWithoutDetaching([$beneficiary->id]);
            $roster->push($beneficiary);
        }

        return compact('admin', 'secretary', 'facultyUser', 'faculty', 'program', 'activity', 'roster');
    }

    /** Data rows in the official header layout (… Pre-Test, Post-Test, Satisfaction). */
    protected function makeXlsx(array $rows): UploadedFile
    {
        return $this->workbookUpload('evaluation.xlsx', function ($sheet) use ($rows) {
            $sheet->fromArray(ActivityEvaluationTemplate::HEADERS, null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
        });
    }

    protected function makeCustomXlsx(array $rows): UploadedFile
    {
        return $this->workbookUpload('custom.xlsx', fn ($sheet) => $sheet->fromArray($rows, null, 'A1'));
    }

    protected function workbookUpload(string $name, callable $fill): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $fill($spreadsheet->getActiveSheet());

        $path = tempnam(sys_get_temp_dir(), 'sc_eval_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    /** @param list<string|int|float> $scores pre, post, satisfaction ('' = blank) */
    protected function rowFor(Beneficiary $beneficiary, string $pre, string $post, string $satisfaction): array
    {
        return [$beneficiary->id, $beneficiary->last_name, $beneficiary->first_name, $beneficiary->barangay, $pre, $post, $satisfaction];
    }

    protected function openParsed(array $data, UploadedFile $file)
    {
        return Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['program' => $data['program']])
            ->call('openRecords', $data['activity']->id)
            ->call('setRecordsTab', 'evaluation')
            ->set('evaluationImportFile', $file)
            ->call('parseEvaluationImport');
    }

    /* ==================== template download ==================== */

    public function test_template_download_is_gated_and_served_to_admin_and_secretary(): void
    {
        $data = $this->seedBase();
        $route = route('activities.evaluation-template', $data['activity']);

        $this->get($route)->assertRedirect('/login');
        $this->actingAs($data['facultyUser'])->get($route)->assertForbidden();

        $response = $this->actingAs($data['secretary'])->get($route);
        $response->assertOk();
        $this->assertStringContainsString(
            ActivityEvaluationTemplate::filename($data['activity']),
            $response->headers->get('content-disposition')
        );

        $this->actingAs($data['admin'])->get($route)->assertOk();
    }

    public function test_downloaded_template_prefills_roster_and_numeric_validations(): void
    {
        $data = $this->seedBase();

        $response = $this->actingAs($data['secretary'])
            ->get(route('activities.evaluation-template', $data['activity']));
        $response->assertOk();

        $sheet = $this->loadStreamedSheet($response);

        foreach (ActivityEvaluationTemplate::HEADERS as $index => $label) {
            $this->assertSame($label, trim((string) $sheet->getCell(chr(65 + $index).'5')->getValue()));
        }

        $roster = $data['activity']->program->beneficiaries()->orderBy('last_name')->get();
        $row = 6;
        foreach ($roster as $beneficiary) {
            $this->assertSame($beneficiary->id, (int) $sheet->getCell("A{$row}")->getValue());
            $this->assertSame($beneficiary->last_name, (string) $sheet->getCell("B{$row}")->getValue());
            $this->assertSame($beneficiary->first_name, (string) $sheet->getCell("C{$row}")->getValue());
            $this->assertSame($beneficiary->barangay, (string) $sheet->getCell("D{$row}")->getValue());
            $this->assertSame('', trim((string) $sheet->getCell("E{$row}")->getValue()));
            $row++;
        }

        // Non-strict decimal ranges: pre/post 0-100, satisfaction 1-5.
        foreach ([['E', '0', '100'], ['F', '0', '100'], ['G', '1', '5']] as [$column, $min, $max]) {
            $validation = $sheet->getCell("{$column}6")->getDataValidation();
            $this->assertSame(DataValidation::TYPE_DECIMAL, $validation->getType(), "Wrong type for {$column}6");
            $this->assertSame(DataValidation::OPERATOR_BETWEEN, $validation->getOperator(), "Wrong operator for {$column}6");
            $this->assertSame($min, (string) $validation->getFormula1(), "Wrong min for {$column}6");
            $this->assertSame($max, (string) $validation->getFormula2(), "Wrong max for {$column}6");
            $this->assertFalse($validation->getShowErrorMessage(), "Validation must be non-strict for {$column}6");
        }
    }

    public function test_template_download_requires_an_enrolled_roster(): void
    {
        $data = $this->seedBase();
        $data['program']->beneficiaries()->detach();

        $this->actingAs($data['secretary'])
            ->get(route('activities.evaluation-template', $data['activity']))
            ->assertStatus(422);
    }

    protected function loadStreamedSheet($response)
    {
        ob_start();
        $response->baseResponse->sendContent();
        $content = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'sc_tpl_').'.xlsx';
        file_put_contents($path, $content);
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $sheet;
    }

    /* ==================== parse ==================== */

    public function test_wrong_headers_are_rejected_with_a_download_hint(): void
    {
        $data = $this->seedBase();

        $this->openParsed($data, $this->makeCustomXlsx([
            ['Name', 'Barangay', 'Score'],
            ['Juan Dela Cruz', 'San Jose', '90'],
        ]))
            ->assertHasErrors('evaluationImportFile')
            ->assertSet('evaluationStep', 'upload');

        $this->assertDatabaseCount('activity_imports', 0);
    }

    public function test_parse_reports_non_numeric_and_out_of_range_scores_per_row(): void
    {
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '45', '65', '4.5'),
            $this->rowFor($second, 'abc', '101', '6'),
        ]))
            ->assertSet('evaluationStep', 'preview')
            ->assertSet('evaluationSummary.processed', 2)
            ->assertSet('evaluationSummary.applied', 1)
            ->assertSet('evaluationSummary.invalid', 1)
            // Projected means only include the applied row.
            ->assertSet('evaluationSummary.means.pre', 45.0)
            ->assertSet('evaluationSummary.means.post', 65.0)
            ->assertSet('evaluationSummary.means.satisfaction', 4.5)
            ->assertSet('evaluationRows.1.state', 'error')
            ->assertSet('evaluationRows.1.errors.0', "Pre-Test Score 'abc' is not a number.")
            ->assertSet('evaluationRows.1.errors.1', 'Post-Test Score 101 is outside the allowed range 0–100.')
            ->assertSet('evaluationRows.1.errors.2', 'Satisfaction 6 is outside the allowed range 1–5.');
    }

    public function test_identity_errors_are_reported_per_row(): void
    {
        $data = $this->seedBase();
        [$first, $second, $third] = $data['roster']->all();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '40', '60', '4'),
            [999999, 'Ghost', 'Ben', 'San Jose', '40', '60', '4'],
            $this->rowFor($first, '40', '60', '4'),
            [$third->id, 'Wrong', $third->first_name, $third->barangay, '40', '60', '4'],
        ]))
            ->assertSet('evaluationStep', 'preview')
            ->assertSet('evaluationSummary.applied', 1)
            ->assertSet('evaluationSummary.invalid', 3)
            ->assertSet('evaluationRows.1.errors.0', 'Beneficiary ID 999999 is not enrolled in this program (unknown or unenrolled).')
            ->assertSet('evaluationRows.2.errors.0', "Beneficiary ID {$first->id} appears more than once in this file.")
            ->assertSet('evaluationRows.3.errors.0', "Name does not match the registry for ID {$third->id} ({$third->last_name}, {$third->first_name}).");

        // $second is simply absent from the file — no error, no row.
        $this->assertSame(0, DB::table('attendances')->count());
    }

    /* ==================== confirm (aggregate + per-metric merge) ==================== */

    public function test_confirm_aggregates_means_into_the_activity_columns(): void
    {
        Storage::fake('public');
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();

        $test = $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '40', '60', '4'),
            $this->rowFor($second, '50', '70', '5'),
        ]))
            ->assertSet('evaluationStep', 'preview')
            ->assertSet('evaluationSummary.means.pre', 45.0)
            ->assertSet('evaluationSummary.means.post', 65.0)
            ->assertSet('evaluationSummary.means.satisfaction', 4.5);

        // D10: nothing is applied before the uploader confirms.
        $this->assertNull($data['activity']->fresh()->pre_assessment_score);

        $test->call('confirmEvaluationImport')->assertSet('recordsActivityId', null);

        $activity = $data['activity']->fresh();
        $this->assertSame(45.0, (float) $activity->pre_assessment_score);
        $this->assertSame(65.0, (float) $activity->post_assessment_score);
        $this->assertSame(4.5, (float) $activity->satisfaction_rating);

        $import = ActivityImport::first();
        $this->assertSame(ActivityImport::TYPE_EVALUATION, $import->type);
        $this->assertSame($data['secretary']->id, $import->imported_by);
        $this->assertSame('evaluation.xlsx', $import->original_name);
        $this->assertSame(2, $import->rows_processed);
        $this->assertSame(2, $import->rows_applied);
        $this->assertSame(45.0, (float) $import->summary['means']['pre']);
        $this->assertSame(65.0, (float) $import->summary['after']['post']);
        $this->assertNull($import->summary['before']['pre']);
        $this->assertNotNull($import->file_path);
        Storage::disk('public')->assertExists($import->file_path);

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'evaluation_import')
            ->where('subject_id', $data['activity']->id)
            ->count());
    }

    public function test_metrics_without_values_keep_existing_aggregates(): void
    {
        $data = $this->seedBase(['pre_assessment_score' => 40]);
        [$first, $second] = $data['roster']->all();

        // File supplies post-test + satisfaction only — pre stays 40.
        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '', '70', '4'),
            $this->rowFor($second, '', '70', '5'),
        ]))
            ->assertSet('evaluationStep', 'preview')
            ->call('confirmEvaluationImport');

        $activity = $data['activity']->fresh();
        $this->assertSame(40.0, (float) $activity->pre_assessment_score);
        $this->assertSame(70.0, (float) $activity->post_assessment_score);
        $this->assertSame(4.5, (float) $activity->satisfaction_rating);

        $summary = ActivityImport::first()->summary;
        $this->assertNull($summary['means']['pre']);
        $this->assertSame(40.0, (float) $summary['after']['pre']);
    }

    public function test_all_blank_scores_report_an_error_and_write_nothing(): void
    {
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '', '', ''),
            $this->rowFor($second, '', '', ''),
        ]))
            ->assertSet('evaluationStep', 'preview')
            ->assertSet('evaluationSummary.skipped', 2)
            ->call('confirmEvaluationImport')
            ->assertHasErrors('evaluationImportFile')
            ->assertSet('evaluationStep', 'preview');

        $this->assertDatabaseCount('activity_imports', 0);
        $activity = $data['activity']->fresh();
        $this->assertNull($activity->pre_assessment_score);
        $this->assertNull($activity->post_assessment_score);
        $this->assertNull($activity->satisfaction_rating);
    }

    public function test_knowledge_gain_derives_after_evaluation_import(): void
    {
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, '40', '60', '4'),
            $this->rowFor($second, '50', '70', '5'),
        ]))->call('confirmEvaluationImport');

        $kpi = app(KpiService::class);
        $this->assertSame(20.0, $kpi->knowledgeGain($data['program']));

        $objective = ProgramObjective::create([
            'extension_program_id' => $data['program']->id,
            'objective' => 'Achieve at least 15 points knowledge gain',
            'kpi_metric' => 'knowledge_gain',
            'target_value' => 15,
        ]);
        $this->assertSame('achieved', $kpi->statusFor($objective));
    }

    /* ==================== gating ==================== */

    public function test_faculty_cannot_parse_or_confirm_evaluation_imports(): void
    {
        $data = $this->seedBase();

        // One Testable per call — an aborted action invalidates the instance.
        foreach (['parseEvaluationImport', 'confirmEvaluationImport'] as $action) {
            Livewire::actingAs($data['facultyUser'])
                ->test(Hub::class, ['program' => $data['program']])
                ->call($action)
                ->assertForbidden();
        }
    }
}
