<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\ActivityImport;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityAttendanceTemplate;
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
 * v4.13 step 6: attendance recording is import-only (blueprint 5.5, 6.19).
 * Template generation, parse (screen-only per D10), confirm effects
 * (upserts, provenance, log, archive) and KPI integration.
 */
class ActivityAttendanceImportTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBase(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0009']);

        $program = ExtensionProject::create([
            'code' => 'EXT-2026-010',
            'title' => 'Attendance Import Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'program_lead_id' => $faculty->id,
        ]);

        $activity = Activity::create([
            'extension_project_id' => $program->id,
            'title' => 'Training Session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'ongoing',
        ]);

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

    /** Data rows in the official header layout (Beneficiary ID … Status). */
    protected function makeXlsx(array $rows): UploadedFile
    {
        return $this->workbookUpload('attendance.xlsx', function ($sheet) use ($rows) {
            $sheet->fromArray(ActivityAttendanceTemplate::HEADERS, null, 'A1');
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

        $path = tempnam(sys_get_temp_dir(), 'sc_att_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    protected function rowFor(Beneficiary $beneficiary, string $status): array
    {
        return [$beneficiary->id, $beneficiary->last_name, $beneficiary->first_name, $beneficiary->barangay, $status];
    }

    protected function openParsed(array $data, UploadedFile $file)
    {
        return Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRecords', $data['activity']->id)
            ->set('attendanceImportFile', $file)
            ->call('parseAttendanceImport');
    }

    /* ==================== template download ==================== */

    public function test_template_download_is_gated_and_served_to_admin_and_secretary(): void
    {
        $data = $this->seedBase();
        $route = route('activities.attendance-template', $data['activity']);

        $this->get($route)->assertRedirect('/login');
        $this->actingAs($data['facultyUser'])->get($route)->assertForbidden();

        $response = $this->actingAs($data['secretary'])->get($route);
        $response->assertOk();
        $this->assertStringContainsString(
            ActivityAttendanceTemplate::filename($data['activity']),
            $response->headers->get('content-disposition')
        );

        $this->actingAs($data['admin'])->get($route)->assertOk();
    }

    public function test_downloaded_template_prefills_roster_and_status_dropdowns(): void
    {
        $data = $this->seedBase();

        $response = $this->actingAs($data['secretary'])
            ->get(route('activities.attendance-template', $data['activity']));
        $response->assertOk();

        $sheet = $this->loadStreamedSheet($response);

        // Official headers live on row 5 (rows 1-3 carry title/context/guide).
        foreach (ActivityAttendanceTemplate::HEADERS as $index => $label) {
            $this->assertSame($label, trim((string) $sheet->getCell(chr(65 + $index).'5')->getValue()));
        }

        // The enrolled roster is pre-filled, sorted by last name.
        $roster = $data['activity']->program->beneficiaries()->orderBy('last_name')->get();
        $row = 6;
        foreach ($roster as $beneficiary) {
            $this->assertSame($beneficiary->id, (int) $sheet->getCell("A{$row}")->getValue());
            $this->assertSame($beneficiary->last_name, (string) $sheet->getCell("B{$row}")->getValue());
            $this->assertSame($beneficiary->first_name, (string) $sheet->getCell("C{$row}")->getValue());
            $this->assertSame($beneficiary->barangay, (string) $sheet->getCell("D{$row}")->getValue());
            $row++;
        }
        $this->assertSame(6 + $roster->count(), $row);

        // Status column carries the non-strict list dropdown; the arrow is
        // visible (PhpSpreadsheet inverts showDropDown vs OOXML — §14).
        $validation = $sheet->getCell('E6')->getDataValidation();
        $this->assertSame(DataValidation::TYPE_LIST, $validation->getType());
        $this->assertSame('"'.implode(',', ActivityAttendanceTemplate::statusLabels()).'"', $validation->getFormula1());
        $this->assertTrue($validation->getShowDropDown());
        $this->assertFalse($validation->getShowErrorMessage());
    }

    public function test_template_download_requires_an_enrolled_roster(): void
    {
        $data = $this->seedBase();
        $data['program']->beneficiaries()->detach();

        $this->actingAs($data['secretary'])
            ->get(route('activities.attendance-template', $data['activity']))
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

    /* ==================== parse (D10: screen-only) ==================== */

    public function test_parse_requires_an_uploaded_file(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRecords', $data['activity']->id)
            ->call('parseAttendanceImport')
            ->assertHasErrors('attendanceImportFile')
            ->assertSet('attendanceStep', 'upload');
    }

    public function test_wrong_headers_are_rejected_with_a_download_hint(): void
    {
        $data = $this->seedBase();

        $this->openParsed($data, $this->makeCustomXlsx([
            ['Name', 'Barangay', 'Remarks'],
            ['Juan Dela Cruz', 'San Jose', 'present'],
        ]))
            ->assertHasErrors('attendanceImportFile')
            ->assertSet('attendanceStep', 'upload');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_parse_requires_an_enrolled_roster(): void
    {
        $data = $this->seedBase();
        $beneficiary = $data['roster']->first();
        $data['program']->beneficiaries()->detach();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($beneficiary, 'Present'),
        ]))
            ->assertHasErrors('attendanceImportFile')
            ->assertSee('enroll them before importing attendance');
    }

    public function test_invalid_rows_are_reported_per_row_and_never_reject_the_file(): void
    {
        $data = $this->seedBase();
        [$first, $second, $third] = $data['roster']->all();

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, 'Present'),
            $this->rowFor($second, 'Maybe'),
            [999999, 'Ghost', 'Ben', 'San Jose', 'Present'],
            $this->rowFor($first, 'Late'),
            [$third->id, 'Wrong', $third->first_name, $third->barangay, 'Absent'],
        ]))
            ->assertSet('attendanceStep', 'preview')
            ->assertSet('attendanceSummary.processed', 5)
            ->assertSet('attendanceSummary.applied', 1)
            ->assertSet('attendanceSummary.invalid', 4)
            ->assertSet('attendanceRows.0.state', 'applied')
            ->assertSet('attendanceRows.1.state', 'error')
            ->assertSet('attendanceRows.1.errors.0', "Status 'Maybe' is not one of Present, Absent, Excused, Late.")
            ->assertSet('attendanceRows.2.errors.0', 'Beneficiary ID 999999 is not enrolled in this program (unknown or unenrolled).')
            ->assertSet('attendanceRows.3.errors.0', "Beneficiary ID {$first->id} appears more than once in this file.")
            ->assertSet('attendanceRows.4.errors.0', "Name does not match the registry for ID {$third->id} ({$third->last_name}, {$third->first_name}).");
    }

    /* ==================== confirm ==================== */

    public function test_preview_never_writes_and_confirm_applies_attendance(): void
    {
        Storage::fake('public');
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();

        $test = $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, 'Present'),
            $this->rowFor($second, 'Late'),
        ]))
            ->assertSet('attendanceStep', 'preview')
            ->assertSet('attendanceSummary.applied', 2)
            ->assertSet('attendanceRows.0.state', 'applied');

        // D10: nothing is written before the uploader confirms.
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseCount('activity_imports', 0);

        $test->call('confirmAttendanceImport')->assertSet('recordsActivityId', null);

        $this->assertSame(2, Attendance::count());
        foreach ([$first->id, $second->id] as $beneficiaryId) {
            $attendance = $data['activity']->attendances()->where('beneficiary_id', $beneficiaryId)->first();
            $this->assertNotNull($attendance);
            $this->assertSame('2026-03-01', $attendance->attendance_date->format('Y-m-d'));
        }
        $this->assertSame('present', $data['activity']->attendances()->where('beneficiary_id', $first->id)->first()->status);
        $this->assertSame('late', $data['activity']->attendances()->where('beneficiary_id', $second->id)->first()->status);

        $import = ActivityImport::first();
        $this->assertSame(ActivityImport::TYPE_ATTENDANCE, $import->type);
        $this->assertSame($data['activity']->id, $import->activity_id);
        $this->assertSame($data['secretary']->id, $import->imported_by);
        $this->assertSame('attendance.xlsx', $import->original_name);
        $this->assertSame(2, $import->rows_processed);
        $this->assertSame(2, $import->rows_applied);
        $this->assertSame(0, $import->rows_skipped);
        $this->assertSame(['present' => 1, 'late' => 1], $import->summary['statuses']);
        $this->assertNotNull($import->file_path);
        Storage::disk('public')->assertExists($import->file_path);

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'attendance_import')
            ->where('subject_id', $data['activity']->id)
            ->count());
    }

    public function test_reimport_updates_existing_attendance_instead_of_duplicating(): void
    {
        $data = $this->seedBase();
        $beneficiary = $data['roster']->first();
        Attendance::create([
            'activity_id' => $data['activity']->id,
            'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-03-01',
            'status' => 'absent',
        ]);

        $this->openParsed($data, $this->makeXlsx([$this->rowFor($beneficiary, 'Present')]))
            ->assertSet('attendanceStep', 'preview')
            ->call('confirmAttendanceImport');

        $records = $data['activity']->attendances()->where('beneficiary_id', $beneficiary->id)->get();
        $this->assertCount(1, $records);
        $this->assertSame('present', $records->first()->status);
    }

    public function test_blank_status_leaves_existing_attendance_untouched(): void
    {
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();
        Attendance::create([
            'activity_id' => $data['activity']->id,
            'beneficiary_id' => $first->id,
            'attendance_date' => '2026-03-01',
            'status' => 'present',
        ]);

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, ''),
            $this->rowFor($second, 'Excused'),
        ]))
            ->assertSet('attendanceSummary.skipped', 1)
            ->assertSet('attendanceSummary.applied', 1)
            ->call('confirmAttendanceImport');

        $this->assertSame(2, Attendance::count());
        $this->assertSame('present', $data['activity']->attendances()->where('beneficiary_id', $first->id)->first()->status);
        $this->assertSame('excused', $data['activity']->attendances()->where('beneficiary_id', $second->id)->first()->status);

        $import = ActivityImport::first();
        $this->assertSame(1, $import->rows_applied);
        $this->assertSame(1, $import->rows_skipped);
    }

    public function test_confirmed_import_feeds_attendance_kpis(): void
    {
        $data = $this->seedBase();
        [$first, $second] = $data['roster']->all();
        $data['program']->beneficiaries()->detach($data['roster']->last()->id);

        $this->openParsed($data, $this->makeXlsx([
            $this->rowFor($first, 'Present'),
            $this->rowFor($second, 'Late'),
        ]))->call('confirmAttendanceImport');

        $kpi = app(KpiService::class);
        $this->assertSame(100.0, $kpi->participationRate($data['program']));
        $this->assertSame(2, $kpi->communityReach($data['program']));
        $this->assertSame(100.0, $kpi->attendanceConsistency($data['program']));
    }

    /* ==================== gating / read-only ==================== */

    public function test_faculty_cannot_parse_or_confirm_attendance_imports(): void
    {
        $data = $this->seedBase();

        // One Testable per call — an aborted action invalidates the instance.
        foreach (['parseAttendanceImport', 'confirmAttendanceImport'] as $action) {
            Livewire::actingAs($data['facultyUser'])
                ->test(Hub::class, ['project' => $data['program']])
                ->call($action)
                ->assertForbidden();
        }
    }

    public function test_faculty_records_modal_is_read_only(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['facultyUser'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRecords', $data['activity']->id)
            ->assertSet('recordsActivityId', $data['activity']->id)
            ->assertSee('Read-only — attendance is recorded')
            ->assertDontSee('Official attendance template');
    }
}
