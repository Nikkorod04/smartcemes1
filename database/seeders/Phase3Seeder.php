<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityProposal;
use App\Models\AvailabilityRequest;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 3 demo workflow data, mapped onto the six 2.4 accounts and the
 * Phase 2 seeded programs/activities. Values mirror the prototype demo
 * (docs/prototype/assets/js/seed-data.js) without contradicting it.
 */
class Phase3Seeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@lnu.com')->first();
        $secretary = User::where('email', 'secretary@lnu.com')->first();
        $facultyBy = fn (string $email) => Faculty::whereHas('user', fn ($q) => $q->where('email', $email))->first();

        $carlo = $facultyBy('faculty1@lnu.com');
        $bianca = $facultyBy('faculty2@lnu.com');
        $nikko = $facultyBy('faculty3@lnu.com');
        $kent = $facultyBy('faculty4@lnu.com');

        $programs = ExtensionProgram::query()->pluck('id', 'code');
        $activities = Activity::query()->pluck('id', 'title');
        $communityIds = DB::table('communities')->pluck('id', 'name');

        // ---------------- Proposals (5.12) ----------------
        $rows = [
            ['faculty' => $nikko, 'program' => 'EXT-2026-002', 'community' => 'Brgy. Apitong', 'title' => 'SOLID Start: Solid Waste Segregation IEC Campaign',
                'start' => '2026-09-01', 'end' => '2026-09-12', 'submitted' => '2026-08-14', 'status' => 'pending', 'budget' => 42000, 'docs' => ['solid-start-proposal.pdf', 'budget-matrix.xlsx']],
            ['faculty' => $bianca, 'program' => 'EXT-2026-001', 'community' => 'Brgy. San Jose', 'title' => 'GULAYAN SA PAARALAN: School Vegetable Gardening Project',
                'start' => '2026-09-10', 'end' => '2026-10-15', 'submitted' => '2026-08-19', 'status' => 'pending', 'budget' => 36500, 'docs' => ['gulayan-proposal.pdf']],
            ['faculty' => $kent, 'program' => 'EXT-2026-005', 'community' => 'Brgy. Salvacion', 'title' => 'PEACE corners: Youth Conflict Resolution Workshops',
                'start' => '2026-09-01', 'end' => '2026-10-30', 'submitted' => '2026-08-05', 'status' => 'approved', 'budget' => 51000,
                'docs' => ['peace-corners.pdf', 'moa-signed.pdf'], 'special_order' => true, 'approved_at' => '2026-08-08',
                'activity' => 'PEACE corners: Youth Conflict Resolution Workshop 1', 'activity_start' => '2026-09-12'],
            ['faculty' => $carlo, 'program' => 'EXT-2026-004', 'community' => 'Brgy. Sagkahan', 'title' => 'TESDA-Ready: Bread & Pastry NC II Pre-Training',
                'start' => '2026-10-05', 'end' => '2026-11-20', 'submitted' => '2026-07-28', 'status' => 'rejected', 'budget' => 78000,
                'docs' => ['bread-pastry.pdf'], 'rejection' => 'Budget exceeds FY allocation ceiling; revise costing or split into two phases.', 'rejected_at' => '2026-08-02'],
            ['faculty' => $carlo, 'program' => 'EXT-2026-005', 'community' => 'Brgy. San Rafael', 'title' => 'SIKAD BUHAY: Bike Safety & Repair Livelihood Clinic',
                // Dates deliberately OUTSIDE EXT-2026-005's range (2026-07-13 → 2026-12-12)
                // so approving it demonstrates the 8.8 program-range hard block.
                'start' => '2026-12-15', 'end' => '2027-01-10', 'submitted' => '2026-08-22', 'status' => 'pending', 'budget' => 24500, 'docs' => ['sikad-buhay.pdf']],
        ];

        // Demo stub files so seeded attachments/Special Orders are really
        // downloadable from /storage (public disk). PDF paths get a valid
        // minimal one-page PDF so the download opens in PDF viewers.
        $writeStub = function (string $path, string $title): void {
            if (Storage::disk('public')->exists($path)) {
                return;
            }
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                Storage::disk('public')->put($path, $this->minimalPdf($title));
            } else {
                Storage::disk('public')->put($path, "SmartCEMES demo file — {$title}\n(placeholder for demo purposes)\n");
            }
        };

        foreach ($rows as $row) {
            $specialOrderPath = ($row['special_order'] ?? false) ? 'special-orders/demo/peace-corners-special-order.pdf' : null;

            if ($specialOrderPath) {
                $writeStub($specialOrderPath, 'Special Order: PEACE corners');
            }

            foreach ($row['docs'] as $doc) {
                $writeStub('proposal-documents/demo/'.$doc, $doc);
            }

            $proposal = ActivityProposal::create([
                'faculty_id' => $row['faculty']->id,
                'extension_program_id' => $programs[$row['program']],
                'community_id' => DB::table('communities')->where('name', $row['community'])->value('id'),
                'title' => $row['title'],
                'proposed_start_date' => $row['start'],
                'proposed_end_date' => $row['end'],
                'budget_estimate' => $row['budget'],
                'status' => $row['status'],
                'submitted_at' => $row['submitted'],
                'admin_approved_by' => $row['approved_at'] ?? null ? $admin->id : null,
                'admin_approved_at' => $row['approved_at'] ?? null,
                'admin_remarks' => $row['approved_at'] ?? null ? 'Approved as proposed.' : null,
                'special_order_path' => $specialOrderPath,
                'rejection_reason' => $row['rejection'] ?? null,
                'rejected_by' => $row['rejected_at'] ?? null ? $admin->id : null,
                'rejected_at' => $row['rejected_at'] ?? null,
            ]);

            foreach ($row['docs'] as $doc) {
                $proposal->documents()->create([
                    'file_name' => $doc,
                    'file_path' => 'proposal-documents/demo/'.$doc,
                    'file_size' => 240_000,
                    'file_type' => pathinfo($doc, PATHINFO_EXTENSION),
                    'uploaded_at' => $row['submitted'],
                ]);
            }

            // Approved proposals auto-create their draft activity (5.12).
            if (! empty($row['activity'])) {
                $activity = Activity::create([
                    'extension_program_id' => $programs[$row['program']],
                    'activity_proposal_id' => $proposal->id,
                    'title' => $row['activity'],
                    'planned_start_date' => $row['activity_start'] ?? $row['start'],
                    'planned_end_date' => $row['activity_start'] ?? $row['end'],
                    'start_time' => '13:00:00',
                    'end_time' => '16:00:00',
                    'status' => 'draft',
                ]);
                $proposal->update(['created_activity_id' => $activity->id]);
            }
        }

        $this->command->info('  Proposals seeded (2 pending, 1 approved w/ Special Order, 1 rejected, 1 range-violation demo).');

        // ---------------- Availability (5.8) ----------------
        $av = [
            ['activity' => 'Mid-Year Reading Proficiency Evaluation', 'faculty' => $bianca, 'date' => '2026-08-22', 'start' => '08:00:00', 'end' => '11:00:00', 'status' => 'accepted', 'remarks' => 'Proctoring for the mid-year reading evaluation.', 'requested' => '2026-08-10', 'responded' => '2026-08-11'],
            ['activity' => 'Blood Pressure Screening & Wellness Talk', 'faculty' => $bianca, 'date' => '2026-08-30', 'start' => '08:00:00', 'end' => '12:00:00', 'status' => 'pending', 'remarks' => 'BP screening at San Rafael Health Station — needs a lead.', 'requested' => '2026-08-24'],
            ['activity' => 'First-Aid & Water Rescue Training', 'faculty' => $nikko, 'date' => '2026-08-09', 'start' => '09:00:00', 'end' => '16:00:00', 'status' => 'accepted', 'remarks' => 'Lead the water-rescue training.', 'requested' => '2026-06-05', 'responded' => '2026-06-06'],
            ['activity' => 'Blood Pressure Screening & Wellness Talk', 'faculty' => $kent, 'date' => '2026-08-30', 'start' => '08:00:00', 'end' => '12:00:00', 'status' => 'declined', 'remarks' => 'Support BP screening logistics.', 'decline' => 'Class conflict — university accreditation week.', 'requested' => '2026-08-24', 'responded' => '2026-08-25'],
            ['activity' => 'Blood Pressure Screening & Wellness Talk', 'faculty' => $carlo, 'date' => '2026-08-30', 'start' => '13:00:00', 'end' => '17:00:00', 'status' => 'pending', 'remarks' => 'Set up digital literacy demo booth.', 'requested' => '2026-08-24'],
            ['activity' => 'Blood Pressure Screening & Wellness Talk', 'faculty' => $bianca, 'date' => '2026-08-30', 'start' => '13:00:00', 'end' => '17:00:00', 'status' => 'pending', 'remarks' => 'Afternoon registration desk & participant tracking.', 'requested' => '2026-08-25'],
        ];

        foreach ($av as $row) {
            AvailabilityRequest::create([
                'activity_id' => DB::table('activities')->where('title', $row['activity'])->value('id'),
                'faculty_id' => $row['faculty']->id,
                'date' => $row['date'],
                'start_time' => $row['start'],
                'end_time' => $row['end'],
                'status' => $row['status'],
                'requested_by' => $admin->id,
                'requested_at' => $row['requested'],
                'remarks' => $row['remarks'] ?? null,
                'responded_by' => $row['responded'] ?? null ? $row['faculty']->user_id : null,
                'responded_at' => $row['responded'] ?? null,
                'decline_reason' => $row['decline'] ?? null,
            ]);
        }

        $this->command->info('  Availability requests seeded (admin-initiated; accepted/declined/pending demos).');

        // ---------------- Rendered hours (8.9) ----------------
        $rh = [
            ['faculty' => $bianca, 'activity' => 'Pre-Assessment & Reading Camp Kick-off', 'date' => '2026-02-03', 'hours' => 4.0, 'status' => 'approved', 'submitted_at' => '2026-02-05', 'approved_at' => '2026-02-06'],
            ['activity' => 'Remedial Reading Session Batch 3', 'faculty' => $bianca, 'date' => '2026-07-18', 'hours' => 2.5, 'status' => 'pending', 'submitted_at' => '2026-07-20',
                'remarks' => 'Auto-drafted 3.0 | Adjusted from 3.00 to 2.50 hrs: Co-led with Prof. Sumile — partial session.'],
            ['activity' => 'Typhoon Drill & Evacuation Simulation', 'faculty' => $nikko, 'date' => '2026-06-21', 'hours' => 5.0, 'status' => 'approved', 'submitted_at' => '2026-06-23', 'approved_at' => '2026-06-24'],
            ['activity' => 'First-Aid & Water Rescue Training', 'faculty' => $nikko, 'date' => '2026-08-09', 'hours' => 7.0, 'status' => 'pending', 'submitted_at' => '2026-08-11'],
            ['activity' => 'Basic Computer Hands-on Workshop 2', 'faculty' => $carlo, 'date' => '2026-08-16', 'hours' => 4.0, 'status' => 'pending', 'submitted_at' => '2026-08-18'],
            ['activity' => 'Post-Training Product Showcase', 'faculty' => $kent, 'date' => '2026-06-26', 'hours' => 4.0, 'status' => 'approved', 'submitted_at' => '2026-06-27', 'approved_at' => '2026-06-28'],
        ];

        foreach ($rh as $row) {
            $activity = Activity::where('title', $row['activity'])->first();

            RenderedHours::create([
                'faculty_id' => $row['faculty']->id,
                'activity_id' => $activity->id,
                'date' => $row['date'],
                'hours' => $row['hours'],
                'source' => 'auto',
                'status' => $row['status'],
                'submitted_by' => $row['submitted_at'] ?? null ? $row['faculty']->user_id : null,
                'submitted_at' => $row['submitted_at'] ?? null,
                'approved_by' => $row['approved_at'] ?? null ? $admin->id : null,
                'approved_at' => $row['approved_at'] ?? null,
                'remarks' => $row['remarks'] ?? null,
            ]);
        }

        $this->command->info('  Rendered hours seeded (approved entries locked; pending queued for approval).');
    }

    /**
     * Valid minimal one-page PDF (with correct xref offsets) used for
     * seeded demo attachments so downloads open in PDF viewers.
     */
    private function minimalPdf(string $title): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            null, // content stream, built below
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $stream = "BT /F1 14 Tf 72 720 Td (SmartCEMES demo document) Tj ET\nBT /F1 11 Tf 72 700 Td ({$text}) Tj ET";
        $objects[3] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= ($num + 1)." 0 obj\n{$body}\nendobj\n";
        }

        $xrefAt = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefAt}\n%%EOF";

        return $pdf;
    }
}
