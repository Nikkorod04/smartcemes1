<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\RenderedHours;
use App\Models\User;
use App\Services\ProjectArchiveService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * THE SAFE-ZONE PROJECT SET (owner decision 2026-10-05).
 *
 * Replaces the demo's eight seeded projects with five simpler, plainly
 * LNU-plausible ones, and gives each a real cohort so no project renders 0
 * hours — the §26.4 "reads as broken rather than thin" problem.
 *
 * Runs LAST in DatabaseSeeder, so there is something to archive.
 *
 * WHAT IT DOES NOT DO
 * -------------------
 * - It does NOT hard-delete. The existing projects are ARCHIVED through
 *   `ProjectArchiveService`, so their legacy `EXT-2026-00X` codes survive (they
 *   are the floor `ExtensionProject::nextCode()` computes over) and a Director
 *   can restore any of them from the hub's Archived filter.
 * - It does NOT write `ProgramObjective` rows. The 8.6 dictionary is retired
 *   (D-R7) and those rows are unread, so new ones would be dead data.
 * - It does NOT touch `docs/prototype/assets/js/seed-data.js`. §30 pinned
 *   Laravel's rendered hours to that file's per-project `activities[]` array
 *   EXACTLY, so this seeder deliberately breaks that correspondence. That is an
 *   accepted divergence of the same class as the college hub (§23.6) and is
 *   recorded in `revisions.md` — a green prototype harness run does NOT
 *   validate this seeder, because those harnesses assert the prototype's own
 *   data.
 *
 * THE HOURS MATH (why the cohort sizes are what they are)
 * -------------------------------------------------------
 * `TrainingHoursService` is the single implementation: trainors × trainees ×
 * days, where `trainees` is a count of DISTINCT beneficiaries with present/late
 * ATTENDANCE (R-Q1) — `activities.participants` is only a fallback and reads 0
 * for the project's Trainees tile. So attendance rows are what produce the
 * figure, and each project below carries one activity with TWO faculty to give
 * the project a `trainors` reading of 2.
 *
 * Resulting figures (all non-zero, all under 100 %, so D7 stays quiet):
 *   KULTURA  84 / 100 (84.0 %) · NUMERO  90 / 120 (75.0 %) · LINIS 70 / 100 (70.0 %)
 *   PAGKAON 108 / 150 (72.0 %) · DIGITAL 48 / 80  (60.0 %)
 *
 * Idempotent: skips entirely when the five already exist.
 */
class SafeZoneProjectSeeder extends Seeder
{
    /**
     * 36 adult first names. The participants here are community members —
     * parents, out-of-school youth, barangay workers — not pupils, so the
     * roster reads older than the BUSOG feeding cohort.
     */
    private const FIRST = [
        'Adelaida', 'Agapito', 'Alejandro', 'Amelia', 'Anastacio', 'Anselmo',
        'Apolonia', 'Arcadio', 'Arsenio', 'Basilia', 'Benedicto', 'Bernardita',
        'Bienvenido', 'Brigida', 'Camilo', 'Candelaria', 'Ceferino', 'Ciriaco',
        'Diosdado', 'Dolores', 'Dominador', 'Editha', 'Eleuterio', 'Emiliana',
        'Epifania', 'Eriberto', 'Eufemia', 'Eulalio', 'Evangelina', 'Feliciano',
        'Fidel', 'Filomena', 'Fortunato', 'Genoveva', 'Gervacio', 'Gregoria',
    ];

    /** 40 Tacloban / Leyte family names. */
    private const LAST = [
        'Abad', 'Abadiano', 'Abejar', 'Abia', 'Abitona', 'Acierto', 'Aclan', 'Adona',
        'Agravante', 'Alconaba', 'Aldaba', 'Alegro', 'Aljibe', 'Alonzo', 'Ampo', 'Ando',
        'Angcay', 'Aniversario', 'Antiquera', 'Apa', 'Aquino', 'Aragon', 'Arcenal', 'Ariza',
        'Asis', 'Astorga', 'Aurelio', 'Avestruz', 'Ayuste', 'Azura', 'Baay', 'Bacalso',
        'Bacolod', 'Badiang', 'Bagacay', 'Bago', 'Balagapo', 'Baldon', 'Balisacan', 'Baluyot',
    ];

    /**
     * The five projects. `college` and `thrust` follow the APPROVED college →
     * thrust mapping in revisions.md §23.4.
     *
     * Each activity carries `attendees` — the number of that project's cohort
     * who actually attended — which is what the hours formula multiplies.
     *
     * @var array<int, array<string, mixed>>
     */
    private const PROJECTS = [
        [
            'college' => 'CAS',
            'thrust' => 'Cultural Development',
            'title' => 'KULTURA: Local Heritage & Folk Arts Appreciation',
            'description' => 'Barangay youth and adults learn the local folk dances, songs and oral history of their own community.',
            'goals' => 'Keep Leyteño folk arts alive among the youth of Brgy. San Jose and give residents a working knowledge of their own heritage.',
            'community' => 'Brgy. San Jose',
            'lead' => 'faculty6@lnu.com',
            'co_lead' => 'faculty2@lnu.com',
            'budget' => 32000,
            'target_hours' => 100,
            'cohort' => 24,
            'categories' => ['Out-of-School Youth', 'Parent'],
            'start' => '2026-06-08', 'end' => '2026-12-11',
            'venue' => 'Brgy. San Jose Multi-Purpose Hall',
            'partners' => ['Barangay Council of San Jose', 'LNU Center for Culture and the Arts'],
            'activities' => [
                ['title' => 'Heritage Orientation & Folk Arts Lecture', 'days' => 1.0, 'attendees' => 24,
                    'start' => '2026-06-08', 'end' => '2026-06-08', 'from' => '08:00:00', 'to' => '12:00:00',
                    'status' => 'completed', 'satisfaction' => 4.6, 'co_lead' => false,
                    'description' => 'Overview of Leyteño folk traditions, local history and the barangay\'s own oral accounts.'],
                ['title' => 'Folk Dance & Local Songs Workshop', 'days' => 1.0, 'attendees' => 24,
                    'start' => '2026-08-15', 'end' => '2026-08-15', 'from' => '08:00:00', 'to' => '16:00:00',
                    'status' => 'completed', 'satisfaction' => 4.7, 'co_lead' => true,
                    'description' => 'Hands-on workshop on the community\'s folk dances and traditional songs, taught in two groups.'],
                ['title' => 'Cultural Showcase & Culminating Activity', 'days' => 0.5, 'attendees' => 24,
                    'start' => '2026-12-11', 'end' => '2026-12-11', 'from' => '13:00:00', 'to' => '17:00:00',
                    'status' => 'ongoing', 'satisfaction' => null, 'co_lead' => false,
                    'description' => 'Participants present the dances and songs learned to the barangay during the fiesta.'],
            ],
        ],
        [
            'college' => 'COE',
            'thrust' => 'Literacy, Numeracy & Language',
            'title' => 'NUMERO: Numeracy Enhancement Sessions for Grades 1-3',
            'description' => 'Remedial numeracy sessions for primary pupils who are below grade level in basic number operations.',
            'goals' => 'Raise the numeracy proficiency of Grades 1-3 pupils in Brgy. Sagkahan through structured remedial sessions.',
            'community' => 'Brgy. Sagkahan',
            'lead' => 'faculty5@lnu.com',
            'co_lead' => 'faculty2@lnu.com',
            'budget' => 45000,
            'target_hours' => 120,
            'cohort' => 30,
            'categories' => ['Student', 'Parent'],
            'start' => '2026-07-06', 'end' => '2026-12-04',
            'venue' => 'Sagkahan Learning Hub',
            'partners' => ['Sagkahan Barangay Council', 'Sagkahan Elementary School'],
            'activities' => [
                ['title' => 'Numeracy Diagnostic & Baseline Assessment', 'days' => 0.5, 'attendees' => 30,
                    'start' => '2026-07-06', 'end' => '2026-07-06', 'from' => '08:00:00', 'to' => '12:00:00',
                    'status' => 'completed', 'satisfaction' => 4.4, 'co_lead' => false,
                    'description' => 'Screening of each pupil\'s number sense and operations to place them in the right group.'],
                ['title' => 'Number Sense & Basic Operations Sessions', 'days' => 1.0, 'attendees' => 30,
                    'start' => '2026-09-19', 'end' => '2026-09-19', 'from' => '08:00:00', 'to' => '16:00:00',
                    'status' => 'completed', 'satisfaction' => 4.6, 'co_lead' => true,
                    'description' => 'Grouped remedial sessions on place value, addition, subtraction and simple word problems.'],
                ['title' => 'Math Games & Remedial Practice', 'days' => 0.5, 'attendees' => 30,
                    'start' => '2026-12-04', 'end' => '2026-12-04', 'from' => '13:00:00', 'to' => '17:00:00',
                    'status' => 'ongoing', 'satisfaction' => null, 'co_lead' => false,
                    'description' => 'Reinforcement through number games and one-on-one remedial practice before the post-test.'],
            ],
        ],
        [
            'college' => 'CAS',
            'thrust' => 'Environmental Conservation & Disaster Preparedness',
            'title' => 'LINIS: Barangay Solid Waste Segregation & Composting',
            'description' => 'Household-level waste segregation and backyard composting training for a barangay with no materials recovery facility.',
            'goals' => 'Cut the volume of mixed waste leaving Brgy. Apitong by teaching segregation at source and home composting.',
            'community' => 'Brgy. Apitong',
            'lead' => 'faculty3@lnu.com',
            'co_lead' => 'faculty5@lnu.com',
            'budget' => 38000,
            'target_hours' => 100,
            'cohort' => 24,
            'categories' => ['Housewife', 'Barangay Worker'],
            'start' => '2026-05-18', 'end' => '2026-11-27',
            'venue' => 'Brgy. Apitong Covered Court',
            'partners' => ['Barangay Council of Apitong', 'Tacloban City Environment and Natural Resources Office'],
            'activities' => [
                ['title' => 'Barangay Waste Audit & Segregation Orientation', 'days' => 0.5, 'attendees' => 24,
                    'start' => '2026-05-18', 'end' => '2026-05-18', 'from' => '08:00:00', 'to' => '12:00:00',
                    'status' => 'completed', 'satisfaction' => 4.3, 'co_lead' => false,
                    'description' => 'Waste characterization of household discards, then an orientation on the segregation scheme.'],
                ['title' => 'Composting & Materials Recovery Training', 'days' => 1.0, 'attendees' => 24,
                    'start' => '2026-08-22', 'end' => '2026-08-22', 'from' => '07:30:00', 'to' => '16:30:00',
                    'status' => 'completed', 'satisfaction' => 4.7, 'co_lead' => true,
                    'description' => 'Hands-on build of a backyard compost pit and a sorting station for recyclables.'],
                ['title' => 'Clean-Up Drive & Segregation Monitoring', 'days' => 0.5, 'attendees' => 20,
                    'start' => '2026-11-27', 'end' => '2026-11-27', 'from' => '06:30:00', 'to' => '10:30:00',
                    'status' => 'ongoing', 'satisfaction' => null, 'co_lead' => false,
                    'description' => 'Barangay-wide clean-up drive followed by a spot check of household segregation compliance.'],
            ],
        ],
        [
            'college' => 'CME',
            'thrust' => 'Livelihood, Technical & Business Management',
            'title' => 'PAGKAON: Basic Food Processing & Product Costing',
            'description' => 'Food processing and simple costing training so households can turn surplus produce into sellable goods.',
            'goals' => 'Give unemployed mothers and out-of-school youth of Brgy. Salvacion a sellable product and the costing skill to price it.',
            'community' => 'Brgy. Salvacion',
            'lead' => 'faculty4@lnu.com',
            'co_lead' => 'faculty1@lnu.com',
            'budget' => 55000,
            'target_hours' => 150,
            'cohort' => 20,
            'categories' => ['Housewife', 'Out-of-School Youth'],
            'start' => '2026-04-13', 'end' => '2026-10-30',
            'venue' => 'Brgy. Salvacion Covered Court',
            'partners' => ['DTI Leyte', 'Barangay Council of Salvacion'],
            'activities' => [
                ['title' => 'Food Safety & Basic Processing Orientation', 'days' => 0.5, 'attendees' => 20,
                    'start' => '2026-04-13', 'end' => '2026-04-13', 'from' => '08:00:00', 'to' => '12:00:00',
                    'status' => 'completed', 'satisfaction' => 4.5, 'co_lead' => false,
                    'description' => 'Handling, hygiene and shelf-life basics before any hands-on processing.'],
                ['title' => 'Product Processing Hands-On (Banana Chips & Pickles)', 'days' => 2.0, 'attendees' => 20,
                    'start' => '2026-06-22', 'end' => '2026-06-23', 'from' => '08:00:00', 'to' => '16:00:00',
                    'status' => 'completed', 'satisfaction' => 4.8, 'co_lead' => true,
                    'description' => 'Two-day practical on making banana chips and pickled vegetables using locally available produce.'],
                ['title' => 'Costing, Packaging & Labeling Workshop', 'days' => 1.0, 'attendees' => 18,
                    'start' => '2026-10-30', 'end' => '2026-10-30', 'from' => '08:00:00', 'to' => '16:00:00',
                    'status' => 'ongoing', 'satisfaction' => null, 'co_lead' => false,
                    'description' => 'Working out the true cost per unit, setting a selling price, and preparing a simple label.'],
            ],
        ],
        [
            'college' => 'CAS',
            'thrust' => 'Information, Communication & Education',
            'title' => 'DIGITAL: Barangay Records & Online Safety Training',
            'description' => 'Basic computer and online-safety training for barangay workers who keep their records on paper.',
            'goals' => 'Let Brgy. El Reposo keep its records digitally and use online services safely.',
            'community' => 'Brgy. El Reposo',
            'lead' => 'faculty1@lnu.com',
            'co_lead' => 'faculty6@lnu.com',
            'budget' => 30000,
            'target_hours' => 80,
            'cohort' => 16,
            'categories' => ['Barangay Worker', 'Vendor'],
            // This project's co-lead's entries stay PENDING, so the faculty board
            // shows an approval queue rather than a flat all-approved set.
            // Deliberately ONE project: marking every co-lead pending starved the
            // faculty who only ever co-lead (Bianca Oledan) of ANY approved hours.
            'pending_co_lead' => true,
            'start' => '2026-07-20', 'end' => '2026-12-18',
            'venue' => 'El Reposo Barangay Hall',
            'partners' => ['Barangay Council of El Reposo'],
            'activities' => [
                ['title' => 'Basic Computer & Encoding Orientation', 'days' => 0.5, 'attendees' => 16,
                    'start' => '2026-07-20', 'end' => '2026-07-20', 'from' => '13:00:00', 'to' => '17:00:00',
                    'status' => 'completed', 'satisfaction' => 4.4, 'co_lead' => false,
                    'description' => 'Parts of a computer, using a keyboard and mouse, and creating a simple document.'],
                ['title' => 'Hands-On Encoding & File Management', 'days' => 1.0, 'attendees' => 16,
                    'start' => '2026-10-17', 'end' => '2026-10-17', 'from' => '08:00:00', 'to' => '16:00:00',
                    'status' => 'completed', 'satisfaction' => 4.6, 'co_lead' => true,
                    'description' => 'Encoding a barangay record sheet into a spreadsheet, saving and retrieving files.'],
                ['title' => 'Online Safety & Digital Etiquette Session', 'days' => 0.5, 'attendees' => 16,
                    'start' => '2026-12-18', 'end' => '2026-12-18', 'from' => '13:00:00', 'to' => '17:00:00',
                    'status' => 'ongoing', 'satisfaction' => null, 'co_lead' => false,
                    'description' => 'Passwords, phishing, and safe use of online government services.'],
            ],
        ],
    ];

    public function run(): void
    {
        $titles = array_column(self::PROJECTS, 'title');

        if (ExtensionProject::withTrashed()->whereIn('title', $titles)->exists()) {
            $this->command?->warn('  Safe-zone project set already seeded — skipping.');

            return;
        }

        $archive = app(ProjectArchiveService::class);
        $admin = User::where('email', 'admin@lnu.com')->firstOrFail();

        DB::transaction(function () use ($archive, $admin): void {
            /* 1. Archive what is there. Every live project goes, not just the
                  eight known ones — the point of this seeder is that the demo
                  set IS the five below. Soft delete throughout, so nothing is
                  lost and the legacy codes keep their sequence floor. */
            $archived = 0;

            foreach (ExtensionProject::all() as $existing) {
                $archive->archive($existing);
                $archived++;
            }

            /* 2. Create the five. */
            foreach (self::PROJECTS as $offset => $spec) {
                $this->seedProject($spec, $offset, $admin);
            }

            $this->command?->info("  Archived {$archived} project(s); created ".count(self::PROJECTS).' safe-zone projects.');
        });
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function seedProject(array $spec, int $offset, User $admin): void
    {
        $college = College::where('code', $spec['college'])->firstOrFail();
        $thrust = Program::where('title', $spec['thrust'])->firstOrFail();
        $community = Community::where('name', $spec['community'])->firstOrFail();
        $lead = $this->faculty($spec['lead']);
        $coLead = $this->faculty($spec['co_lead']);

        $project = ExtensionProject::create([
            'code' => ExtensionProject::nextCode($college->code, 2026),
            'college_id' => $college->id,
            'program_id' => $thrust->id,
            'title' => $spec['title'],
            'description' => $spec['description'],
            'goals' => $spec['goals'],
            'planned_start_date' => $spec['start'],
            'planned_end_date' => $spec['end'],
            'target_beneficiaries' => $spec['cohort'],
            'beneficiary_categories' => $spec['categories'],
            'allocated_budget' => $spec['budget'],
            // Settable from the hub's Edit modal since §26; set here so the
            // project renders an attainment figure rather than "no annual target".
            'annual_target_hours' => $spec['target_hours'],
            'program_lead_id' => $lead->id,
            'partners' => $spec['partners'],
            'status' => 'ongoing',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $project->communities()->sync([$community->id]);

        $beneficiaryIds = $this->seedCohort($project, $spec, $community, $offset);

        foreach ($spec['activities'] as $row) {
            $this->seedActivity($project, $row, $beneficiaryIds, $lead, $coLead, $spec['venue'], $admin, (bool) ($spec['pending_co_lead'] ?? false));
        }

        $this->seedBudget($project, $spec);
    }

    /**
     * Create the project's roster and enroll it.
     *
     * Names cycle through FIRST/LAST with a per-project offset, so no two
     * projects repeat the same (first, last) pair and the roster reads like a
     * real barangay list rather than one clan.
     *
     * @param  array<string, mixed>  $spec
     * @return array<int, int>
     */
    private function seedCohort(ExtensionProject $project, array $spec, Community $community, int $offset): array
    {
        $ids = [];
        $firstCount = count(self::FIRST);
        $lastCount = count(self::LAST);

        for ($i = 0; $i < $spec['cohort']; $i++) {
            $beneficiary = Beneficiary::create([
                'first_name' => self::FIRST[$i % $firstCount],
                'last_name' => self::LAST[($i + $offset * 7) % $lastCount],
                'age' => 18 + (($i * 7 + $offset * 3) % 45),
                'gender' => $i % 2 === 0 ? 'Female' : 'Male',
                'phone' => '09'.str_pad((string) (170000000 + $offset * 1000000 + $i * 4321), 9, '0', STR_PAD_LEFT),
                'barangay' => str_replace('Brgy. ', '', $community->name),
                'municipality' => $community->municipality,
                'province' => 'Leyte',
                'community_id' => $community->id,
                'beneficiary_category' => $spec['categories'][$i % count($spec['categories'])],
            ]);

            $ids[] = $beneficiary->id;
        }

        $project->beneficiaries()->syncWithoutDetaching($ids);

        return $ids;
    }

    /**
     * One activity, its attendance, and its rendered-hours entries.
     *
     * `no_of_days` is set explicitly (a NULL already counts as 1.0, but a half
     * day must be a real 0.5) and `participants` mirrors the attendee count so
     * the R-Q1 fallback cannot disagree with the imported attendance.
     *
     * RENDERED HOURS (8.9) are the OTHER half of the picture and the reason this
     * method takes `$admin`: the faculty board's headline figure is
     * `SUM(rendered_hours.hours) WHERE status = approved`, so without these rows
     * every professor reads "0.0 hours rendered" no matter how many projects they
     * lead. A completed activity auto-drafts one entry per assigned faculty with
     * `hours = end − start` — deliberately NOT multiplied by `no_of_days`, which
     * is the 8.9 rule (a two-day session still drafts its daily duration).
     *
     * `$pendingCoLead` comes from ONE project's spec (see `pending_co_lead`), not
     * from "every co-lead": a faculty member who only ever co-leads would then
     * have no approved hours at all, which is the very bug this is fixing.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, int>  $beneficiaryIds
     */
    private function seedActivity(
        ExtensionProject $project,
        array $row,
        array $beneficiaryIds,
        Faculty $lead,
        Faculty $coLead,
        string $venue,
        User $admin,
        bool $pendingCoLead = false,
    ): void {
        $activity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => $row['title'],
            'description' => $row['description'],
            'planned_start_date' => $row['start'],
            'planned_end_date' => $row['end'],
            'start_time' => $row['from'],
            'end_time' => $row['to'],
            'venue' => $venue,
            'status' => $row['status'],
            'no_of_days' => $row['days'],
            'participants' => $row['attendees'],
            'satisfaction_rating' => $row['satisfaction'],
        ]);

        $assigned = $row['co_lead'] ? [$lead, $coLead] : [$lead];

        $activity->faculty()->syncWithoutDetaching(array_map(fn (Faculty $f) => $f->id, $assigned));

        // Only the first `attendees` of the cohort attend this session — a real
        // roster is never 100 % for every session, and it is what makes the
        // per-activity trainee count (and therefore the hours) vary.
        foreach (array_slice($beneficiaryIds, 0, $row['attendees']) as $index => $beneficiaryId) {
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiaryId,
                'attendance_date' => $row['start'],
                // A couple of latecomers per session, as in the BUSOG cohort.
                'status' => $index % 11 === 0 && $index > 0 ? 'late' : 'present',
            ]);
        }

        // Only a COMPLETED activity produces a draft — the lifecycle's own rule.
        if ($row['status'] !== 'completed') {
            return;
        }

        foreach ($assigned as $faculty) {
            /* One project's co-lead entries are left PENDING so the board shows
               an approval queue; everything else is approved, so no faculty
               member is left with a 0.0 headline. */
            $isCoLead = $row['co_lead'] && $faculty->id === $coLead->id;
            $status = ($pendingCoLead && $isCoLead)
                ? RenderedHours::STATUS_PENDING
                : RenderedHours::STATUS_APPROVED;

            RenderedHours::create([
                'faculty_id' => $faculty->id,
                'activity_id' => $activity->id,
                'date' => $row['start'],
                'hours' => $this->duration($row['from'], $row['to']),
                'source' => RenderedHours::SOURCE_AUTO,
                'status' => $status,
                'submitted_by' => $faculty->user_id,
                'submitted_at' => $row['start'].' 17:00:00',
                'approved_by' => $status === RenderedHours::STATUS_APPROVED ? $admin->id : null,
                'approved_at' => $status === RenderedHours::STATUS_APPROVED ? $row['start'].' 18:00:00' : null,
            ]);
        }
    }

    /** Session duration in hours — the 8.9 auto-draft rule (end − start). */
    private function duration(string $from, string $to): float
    {
        return round((strtotime($to) - strtotime($from)) / 3600, 1);
    }

    /**
     * A few budget entries, including one project-wide row with NO activity.
     *
     * That NULL `activity_id` is deliberate: it is the shape all three original
     * project seeders use, and it is exactly the row a per-activity cascade
     * misses — the bug `ProjectArchiveService` had to fix.
     *
     * @param  array<string, mixed>  $spec
     */
    private function seedBudget(ExtensionProject $project, array $spec): void
    {
        $activityIds = $project->activities()->orderBy('planned_start_date')->pluck('id')->all();

        $rows = [
            ['activity' => 0, 'item' => 'Training materials & handouts', 'amount' => round($spec['budget'] * 0.18), 'date' => $spec['start']],
            ['activity' => 1, 'item' => 'Session supplies & venue provisions', 'amount' => round($spec['budget'] * 0.30), 'date' => $spec['activities'][1]['start']],
            ['activity' => null, 'item' => 'Certificates, tarpaulin & documentation', 'amount' => round($spec['budget'] * 0.14), 'date' => $spec['end']],
        ];

        foreach ($rows as $i => $row) {
            BudgetUtilization::create([
                'extension_project_id' => $project->id,
                'activity_id' => $row['activity'] === null ? null : ($activityIds[$row['activity']] ?? null),
                'item_name' => $row['item'],
                'amount' => $row['amount'],
                'date_used' => $row['date'],
                'receipt_reference' => 'REC-2026-'.$project->id.'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
            ]);
        }
    }

    private function faculty(string $email): Faculty
    {
        return Faculty::whereHas('user', fn ($q) => $q->where('email', $email))->firstOrFail();
    }
}
