<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Import;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use App\Services\AssessmentTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

class AssessmentImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a real XLSX in the official vertical template layout (title,
     * instruction, section separators, label in A / answer in B / junk
     * guide text in C). $answers maps FIELD names to values.
     */
    protected function makeXlsx(array $answers, bool $withJunkRow = false): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'SmartCEMES — Official Needs-Assessment Import Template v2 · Leyte Normal University CESO');
        $sheet->setCellValue('A2', 'One file = one respondent. Fill column B only.');

        $row = 4;
        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (isset(AssessmentTemplate::SECTION_BREAKS[$field])) {
                $sheet->setCellValue("A{$row}", AssessmentTemplate::SECTION_BREAKS[$field]);
                $row++;
            }

            $sheet->setCellValue("A{$row}", $col['label']);
            $sheet->setCellValue("C{$row}", 'guide junk: '.$col['label']); // column C must be ignored

            if (array_key_exists($field, $answers)) {
                $sheet->setCellValue("B{$row}", $answers[$field]);
            }

            if ($withJunkRow && $field === 'respondent_sex') {
                $row++;
                $sheet->setCellValue("A{$row}", 'Random Note (not a field)');
                $sheet->setCellValue("B{$row}", 'should be ignored');
            }

            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'sc_assess_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('assessment.xlsx', $contents);
    }

    protected function responseData(): array
    {
        return [
            'respondent_first_name' => 'Juan',
            'respondent_middle_name' => 'Reyes',
            'respondent_last_name' => 'Dela Cruz',
            'respondent_age' => '34',
            'respondent_civil_status' => 'Married',
            'respondent_sex' => 'Male',
            'respondent_religion' => 'Roman Catholic',
            'respondent_educational_attainment' => 'College Graduate',
            'livelihood_options' => 'Farming',
            'household_member_currently_studying' => 'Yes',
            'interested_in_continuing_studies' => 'No',
            'has_barangay_health_programs' => 'Yes',
            'benefits_from_barangay_programs' => 'Yes',
            'has_own_toilet' => 'Yes',
            'keeps_animals' => 'No',
            'member_of_organization' => 'No',
            'has_electricity' => 'Yes',
            'family_problems' => 'Low income, Poor housing',
            'barangay_service_ratings' => 'Good',
            'available_for_training' => 'Yes',
        ];
    }

    /* ==================== import flow ==================== */

    public function test_upload_form_is_visible_on_initial_render(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->assertSee('Official template (.xlsx)')
            ->assertSee('Parse file')
            ->assertDontSee('Confirm & create record');
    }

    public function test_import_page_links_back_to_encoding_form(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->assertSeeHtml('href="'.route('assessments.create').'"');
    }

    public function test_year_dropdown_covers_past_and_next_five_years(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->assertSee((string) (now()->year - 5))
            ->assertSee((string) now()->year)
            ->assertSee((string) (now()->year + 5))
            ->assertDontSee((string) (now()->year - 6));
    }

    public function test_faculty_imports_filled_template_end_to_end(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $file = $this->makeXlsx([
            'community_name' => 'Brgy. San Jose',
            'quarter' => '3',
            'year' => '2026',
            ...$this->responseData(),
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('communityId', $community->id)
            ->assertSet('quarter', 3)
            ->assertSet('year', 2026)
            ->assertSet('record.respondent_first_name', 'Juan')
            ->assertSet('record.respondent_middle_name', 'Reyes')
            ->assertSet('record.respondent_last_name', 'Dela Cruz')
            ->assertSet('record.respondent_age', 34)
            ->assertSet('record.respondent_sex', 'Male')
            ->assertSet('record.respondent_civil_status', 'Married')
            ->assertSet('record.livelihood_options', 'Farming')
            ->assertSet('record.has_electricity', 'Yes')
            ->assertSet('record.barangay_service_ratings', ['overall' => 'Good'])
            ->assertSet('fieldErrors', [])
            ->assertSeeHtml('Confirm & create record')
            ->assertDontSee('Parse file')
            ->call('confirm')
            ->assertSet('step', 'upload');

        $this->assertDatabaseHas('needs_assessments', [
            'community_id' => $community->id,
            'quarter' => 3,
            'year' => 2026,
            'respondent_first_name' => 'Juan',
            'respondent_middle_name' => 'Reyes',
            'respondent_last_name' => 'Dela Cruz',
            'respondent_age' => 34,
            'respondent_sex' => 'Male',
            'review_status' => 'pending',
            'uploaded_by' => $faculty->id,
        ]);
        $this->assertDatabaseCount('needs_assessments', 1);

        $assessment = NeedsAssessment::first();
        $this->assertNotNull($assessment->file_path);
        $this->assertSame('Farming', $assessment->livelihood_options);
    }

    public function test_context_block_supplies_community_quarter_year_without_form_input(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $community = Community::factory()->create(['name' => 'Brgy. El Reposo']);

        $file = $this->makeXlsx([
            'community_name' => 'brgy. el reposo',
            'quarter' => '4',
            'year' => '2026',
            'respondent_first_name' => 'Maria',
            'respondent_last_name' => 'Santos',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('communityId', $community->id)
            ->assertSet('quarter', 4)
            ->assertSet('year', 2026);
    }

    public function test_blank_community_in_template_falls_back_to_form_values(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $community = Community::factory()->create(['name' => 'Brgy. Salvacion']);

        // Community left blank in the template — the uploader picks it in the form.
        $file = $this->makeXlsx($this->responseData());

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('communityId', $community->id)
            ->set('year', 2026)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('record.respondent_first_name', 'Juan')
            ->assertSet('record.respondent_age', 34);
    }

    public function test_parse_stays_on_upload_step_when_community_and_year_are_missing(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $file = $this->makeXlsx($this->responseData());

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertHasErrors('communityId')
            ->assertSet('step', 'upload');
    }

    public function test_separator_guide_and_unknown_label_rows_are_ignored(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $community = Community::factory()->create(['name' => 'Brgy. 95']);

        // makeXlsx writes junk guide text into column C on every row and,
        // with the flag, an unknown-label row mid-template — none of it may
        // affect parsing (labels AFTER the junk row must still map).
        $file = $this->makeXlsx([
            'community_name' => 'Brgy. 95',
            'quarter' => '1',
            'year' => '2026',
            'respondent_first_name' => 'Elena',
            'respondent_last_name' => 'Dizon',
            'respondent_sex' => 'Female',
            'respondent_religion' => 'Roman Catholic',
            'household_member_currently_studying' => 'Yes',
            'interested_in_continuing_studies' => 'No',
            'has_barangay_health_programs' => 'Yes',
            'benefits_from_barangay_programs' => 'Yes',
            'has_own_toilet' => 'Yes',
            'keeps_animals' => 'No',
            'member_of_organization' => 'No',
            'has_electricity' => 'Yes',
            'available_for_training' => 'Yes',
        ], withJunkRow: true);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('communityId', $community->id)
            ->assertSet('record.respondent_first_name', 'Elena')
            ->assertSet('record.respondent_sex', 'Female')
            ->assertSet('record.respondent_religion', 'Roman Catholic')
            ->assertSet('fieldErrors', []);
    }

    public function test_reason_not_available_accepts_canonical_values_only(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Community::factory()->create(['name' => 'Brgy. 95']);

        $file = $this->makeXlsx([
            'community_name' => 'Brgy. 95',
            'quarter' => '1',
            'year' => '2026',
            'respondent_first_name' => 'Pedro',
            'respondent_last_name' => 'Ramos',
            'available_for_training' => 'No',
            'reason_not_available' => 'work schedule conflict',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('record.reason_not_available', 'Work schedule conflict')
            ->assertSet('record.available_for_training', 'No')
            ->call('confirm');

        $this->assertDatabaseHas('needs_assessments', [
            'reason_not_available' => 'Work schedule conflict',
            'available_for_training' => 'No',
        ]);

        // v4.9: reason is a closed list — free text becomes a per-field
        // error and is dropped (never a whole-file reject).
        $file = $this->makeXlsx([
            'community_name' => 'Brgy. 95',
            'quarter' => '1',
            'year' => '2026',
            'respondent_first_name' => 'Ana',
            'respondent_last_name' => 'Cruz',
            'available_for_training' => 'No',
            'reason_not_available' => 'Busy with farm work',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('fieldErrors.reason_not_available', 'Value "Busy with farm work" is not in the standard list');
    }

    public function test_invalid_age_is_reported_per_field_and_never_rejects_the_file(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $community = Community::factory()->create(['name' => 'Brgy. Pago']);

        $file = $this->makeXlsx([
            'community_name' => 'Brgy. Pago',
            'quarter' => '2',
            'year' => '2026',
            'respondent_first_name' => 'Lito',
            'respondent_last_name' => 'Bacsal',
            'respondent_age' => 'abc',
            'respondent_sex' => 'Male',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('record.respondent_first_name', 'Lito')
            ->assertSet('record.respondent_sex', 'Male')
            ->assertSet('fieldErrors.respondent_age', 'Expected an age between 15 and 120, got "abc"')
            ->call('confirm');

        // The invalid field is dropped (D9/D10: on-screen only, never a
        // whole-file reject) — everything else imports.
        $this->assertDatabaseHas('needs_assessments', [
            'community_id' => $community->id,
            'respondent_first_name' => 'Lito',
            'respondent_sex' => 'Male',
            'respondent_age' => null,
        ]);
    }

    public function test_unknown_single_value_auto_maps_to_other_with_raw_text_kept(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Community::factory()->create(['name' => 'Brgy. Utap']);

        // Main livelihood is single-select and keeps "Other" (D9).
        $file = $this->makeXlsx([
            'community_name' => 'Brgy. Utap',
            'quarter' => '1',
            'year' => '2026',
            'respondent_first_name' => 'Nena',
            'respondent_last_name' => 'Loreto',
            'livelihood_options' => 'Odd jobs',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('record.livelihood_options', 'Other')
            ->assertSet('otherMapped.livelihood_options', ['Odd jobs'])
            ->call('confirm');

        $assessment = NeedsAssessment::first();
        $this->assertSame(['livelihood_options' => ['Odd jobs']], $assessment->other_text);
    }

    public function test_closed_list_religion_rejects_unknown_values_per_field(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Community::factory()->create(['name' => 'Brgy. Utap']);

        // v4.9: religion has no "Other" — 'Buddhist' (vs canonical
        // 'Buddhism') is a per-field error, dropped but never a whole-file
        // reject.
        $file = $this->makeXlsx([
            'community_name' => 'Brgy. Utap',
            'quarter' => '1',
            'year' => '2026',
            'respondent_first_name' => 'Nena',
            'respondent_last_name' => 'Loreto',
            'respondent_religion' => 'Buddhist',
        ]);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'preview')
            ->assertSet('record.respondent_first_name', 'Nena')
            ->assertSet('fieldErrors.respondent_religion', 'Value "Buddhist" is not in the standard list');
    }

    public function test_files_without_template_labels_are_rejected(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        // Horizontal grid (like the retired v1 template) — rejected with a
        // re-download hint.
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Community', 'Quarter (1-4)', 'Year', 'Respondent First Name', 'Respondent Last Name'], null, 'A1');
        $sheet->fromArray(['Brgy. San Jose', '3', '2026', 'Juan', 'Dela Cruz'], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'sc_bad_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        $file = UploadedFile::fake()->createWithContent('wrong.xlsx', $contents);

        Livewire::actingAs($faculty)
            ->test(Import::class)
            ->set('file', $file)
            ->call('parse')
            ->assertHasErrors('file')
            ->assertSet('step', 'upload');
    }

    /* ==================== template download ==================== */

    public function test_downloaded_template_is_vertical_with_guides_and_dropdowns(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $response = $this->actingAs($faculty)->get(route('assessments.template'));

        $response->assertOk();
        $this->assertStringContainsString(AssessmentTemplate::TEMPLATE_FILENAME, $response->headers->get('content-disposition'));

        // Capture the streamed download and load it back as a workbook.
        ob_start();
        $response->baseResponse->sendContent();
        $content = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'sc_tpl_').'.xlsx';
        file_put_contents($tmp, $content);
        $sheet = IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $labelToRow = [];
        $sections = [];
        $dropdownCells = [];
        for ($r = 1; $r <= $sheet->getHighestRow(); $r++) {
            $a = trim((string) $sheet->getCell("A{$r}")->getValue());
            if ($a !== '') {
                $labelToRow[$a] = $r;
                if (str_starts_with($a, 'Section ') || $a === 'Import Context') {
                    $sections[] = $a;
                }
            }

            $validation = $sheet->getCell("B{$r}")->getDataValidation();
            if ($validation->getType() === DataValidation::TYPE_LIST) {
                $dropdownCells[$a] = $validation;
            }
        }

        // Every template label is present in column A.
        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            $this->assertArrayHasKey($col['label'], $labelToRow, "Missing label for {$field}");
        }

        // Context block + all nine section separators.
        $this->assertCount(10, $sections);
        $this->assertContains('Section I — Respondent Information', $sections);
        $this->assertContains('Section IX — Service Ratings and Summary', $sections);

        // Fill-up guides are authored in column C.
        $nameRow = $labelToRow['First Name'];
        $this->assertSame(AssessmentTemplate::guide('respondent_first_name'), (string) $sheet->getCell("C{$nameRow}")->getValue());
        $this->assertStringContainsString('Choose one', (string) $sheet->getCell('C'.$labelToRow['Main Source of Household Livelihood'])->getValue());
        $this->assertStringContainsString('up to 3', (string) $sheet->getCell('C'.$labelToRow['Family Problems'])->getValue());

        // Non-strict dropdowns: 9 yes/no + 25 single = 34 cells.
        $this->assertCount(34, $dropdownCells);

        // Arrows visible (PhpSpreadsheet inverts the property vs OOXML) and
        // errors disabled so typed free text is accepted (D9).
        foreach ($dropdownCells as $label => $validation) {
            $this->assertTrue($validation->getShowDropDown(), "Dropdown arrow hidden for {$label}");
            $this->assertFalse($validation->getShowErrorMessage(), "Validation must be non-strict for {$label}");
        }

        // Multi-select fields get guides only — no dropdown (comma-separated
        // typing would fight a single-pick list); single-select fields DO
        // get dropdowns (Main Source of Household Livelihood included).
        $this->assertArrayHasKey('Main Source of Household Livelihood', $dropdownCells);
        $this->assertArrayNotHasKey('Family Problems', $dropdownCells);
        $this->assertArrayNotHasKey('Animals Kept', $dropdownCells);
        $this->assertSame('"Yes,No"', $dropdownCells['Has Electricity']->getFormula1());
        $this->assertSame(
            '"'.implode(',', config('smartcemes.vocab.respondent_civil_status')).'"',
            $dropdownCells['Civil Status']->getFormula1()
        );

        // The religion list exceeds Excel's 255-char inline cap — it must
        // fall back to a hidden Lists-sheet range.
        $this->assertStringStartsWith("'Lists'!", $dropdownCells['Religion']->getFormula1());
        $this->assertGreaterThan(255, strlen('"'.implode(',', config('smartcemes.vocab.respondent_religion')).'"'));
    }
}
