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
use App\Models\NeedsAssessment;
use App\Models\Program;
use App\Models\ProgramObjective;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 demo data — remapped per AI_HANDOFF §7 rules:
 * - legacy values re-keyed to Section 7 vocabularies
 *   ('Catholic'→'Roman Catholic', 'NAWASA'→'Level II / piped',
 *    'Oil lamp'→'Kerosene lamp', 'Half concrete/wood'→'Semi-concrete', …)
 * - integer quarters, JSON arrays of strings
 * - matches docs/prototype/assets/js/seed-data.js where richer
 *   (6 programs incl. HANDA over-allocation, communities with address/email,
 *   programObjectives, budgetEntries, beneficiaries)
 */
class Phase2Seeder extends Seeder
{
    protected array $communityIds = [];

    protected array $programIds = [];

    protected array $enrolledByProgram = [];

    public function run(): void
    {
        $this->seedCommunities();
        $this->seedPrograms();
        $this->seedBeneficiaries();
        $this->seedActivities();
        $this->seedAttendance();
        $this->seedObjectives();
        $this->seedBudgets();
        $this->seedNeedsAssessments();
    }

    private function seedCommunities(): void
    {
        $rows = [
            ['name' => 'Brgy. San Jose', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Purok 3, near San Jose Elementary School', 'contact_person' => 'Kagawad Roberto Tan', 'contact_number' => '0917 553 2210', 'email' => 'sanjose.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay with an active elementary school community.'],
            ['name' => 'Brgy. Sagkahan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Sagkahan Rd', 'contact_person' => 'Capt. Erlinda Ybañez', 'contact_number' => '0928 447 1105', 'email' => 'sagkahan.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Dense residential barangay hosting the Sagkahan Learning Hub.'],
            ['name' => 'Brgy. El Reposo', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Coastal road, Barangay Hall', 'contact_person' => 'Kagawad Marissa Dolina', 'contact_number' => '0935 220 8764', 'email' => 'elreposo.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Coastal households exposed to storm surge; HANDA target area.'],
            ['name' => 'Brgy. Salvacion', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Salvacion proper, near covered court', 'contact_person' => 'Capt. Rodrigo Amistoso', 'contact_number' => '0946 118 3390', 'email' => 'salvacion.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Livelihood-focused barangay; KABUHIAN pilot community.'],
            ['name' => 'Brgy. San Rafael', 'municipality' => 'Dulag', 'province' => 'Leyte', 'address' => 'Health station compound', 'contact_person' => 'Kagawad Teresita Bionat', 'contact_number' => '0912 884 5571', 'email' => 'sanrafael.barangay@dulag.gov.ph', 'status' => 'active', 'description' => 'Home of the San Rafael Health Station partnership.'],
            ['name' => 'Brgy. Apitong', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Apitong', 'contact_person' => 'Kagawad Noel Sabalza', 'contact_number' => '0999 512 0087', 'email' => 'apitong.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Prospecting pipeline — needs assessment visit being scheduled.'],

            // ---- Tacloban City expansion (verified vs PhilAtlas barangay list) ----
            ['name' => 'Brgy. Suhi', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Suhi', 'contact_person' => 'Capt. John Mark Flores', 'contact_number' => '0917 302 4451', 'email' => 'suhi.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Fast-growing residential barangay along the bypass road.'],
            ['name' => 'Brgy. Santo Niño', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Sto. Niño', 'contact_person' => 'Kagawad Maria Angela Cinco', 'contact_number' => '0918 634 7712', 'email' => 'santonino.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Rapidly expanding relocation and housing site.'],
            ['name' => 'Brgy. Abucay', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Abucay', 'contact_person' => 'Capt. Jan Michael Mendoza', 'contact_number' => '0919 445 2210', 'email' => 'abucay.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'One of the city\'s most populous barangays; active parent-teacher community.'],
            ['name' => 'Brgy. Cabalawan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Near San Juanico Bridge approach', 'contact_person' => 'Kagawad Angelica Garcia', 'contact_number' => '0920 118 3345', 'email' => 'cabalawan.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay near the San Juanico Bridge approach.'],
            ['name' => 'Brgy. Bagacay', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Bagacay', 'contact_person' => 'Capt. Joshua Villamor', 'contact_number' => '0921 552 8890', 'email' => 'bagacay.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Dense residential barangay; Bagacay Elementary School partner area.'],
            ['name' => 'Brgy. Utap', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Utap', 'contact_person' => 'Kagawad Mary Grace Dela Cruz', 'contact_number' => '0930 224 6601', 'email' => 'utap.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Northern barangay; Utap Elementary School partnership being explored.'],
            ['name' => 'Brgy. Diit', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Diit', 'contact_person' => 'Capt. Christian Fernandez', 'contact_number' => '0931 887 1123', 'email' => 'diit.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Mixed residential-agricultural barangay in the city\'s northern district.'],
            ['name' => 'Brgy. Calanipawan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Calanipawan', 'contact_person' => 'Kagawad Jennifer Canete', 'contact_number' => '0932 440 9987', 'email' => 'calanipawan.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Established residential barangay near the city center.'],
            ['name' => 'Brgy. Caibaan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Caibaan', 'contact_person' => 'Capt. Mark Anthony Pepito', 'contact_number' => '0933 661 2204', 'email' => 'caibaan.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Well-established barangay; Caibaan Elementary School partner area.'],
            ['name' => 'Brgy. V & G Subdivision', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'V&G Subdivision, Barangay Hall', 'contact_person' => 'Kagawad Rhea Mae De Paz', 'contact_number' => '0935 773 4489', 'email' => 'vg.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Planned residential subdivision community with active homeowners association.'],
            ['name' => 'Brgy. Tagapuro', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Tagapuro', 'contact_person' => 'Capt. Jeffrey Sanchez', 'contact_number' => '0936 129 5570', 'email' => 'tagapuro.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Northern resettlement-area barangay; needs assessment scheduled.'],
            ['name' => 'Brgy. Palanog', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Palanog', 'contact_person' => 'Kagawad Michael Lopez', 'contact_number' => '0937 208 9914', 'email' => 'palanog.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Barangay near the airport; Palanog Elementary School partnership pending.'],
            ['name' => 'Brgy. New Kawayan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, New Kawayan', 'contact_person' => 'Capt. Princess Gonzales', 'contact_number' => '0938 445 7723', 'email' => 'newkawayan.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Resettlement barangay; household listing underway for needs assessment.'],
            ['name' => 'Brgy. San Roque', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, San Roque', 'contact_person' => 'Kagawad Kevin Rosales', 'contact_number' => '0939 330 1168', 'email' => 'sanroque.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Northern coastal barangay with fishing households.'],
            ['name' => 'Brgy. Tigbao', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Tigbao', 'contact_person' => 'Capt. Arvin Gonzaga', 'contact_number' => '0942 558 3317', 'email' => 'tigbao.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Residential barangay near the Tacloban City Consortium area.'],
            ['name' => 'Brgy. Nula-tula', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Nula-tula', 'contact_person' => 'Kagawad Michelle Avila', 'contact_number' => '0943 661 2290', 'email' => 'nulatula.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Coastal-residential barangay; Nula-Tula Elementary School partner area.'],
            ['name' => 'Brgy. Basper', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Basper', 'contact_person' => 'Capt. Ryan Malinao', 'contact_number' => '0944 773 8845', 'email' => 'basper.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Commercial-residential barangay along the highway.'],
            ['name' => 'Brgy. Santa Elena', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Santa Elena', 'contact_person' => 'Kagawad Jayson Diaz', 'contact_number' => '0945 112 6634', 'email' => 'santaelena.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Upland agricultural barangay in the city\'s north.'],
            ['name' => 'Brgy. Camansinay', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Camansinay', 'contact_person' => 'Capt. Lovely Mae Martinez', 'contact_number' => '0946 995 2278', 'email' => 'camansinay.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Quiet residential barangay near Cabalawan.'],
            ['name' => 'Brgy. Marasbaras', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Marasbaras', 'contact_person' => 'Kagawad Vincent Morales', 'contact_number' => '0947 220 1146', 'email' => 'marasbaras.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Expanding residential district; Marasbaras National High School partner area.'],
            ['name' => 'Brgy. Libertad', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Libertad', 'contact_person' => 'Capt. John Paul Laurente', 'contact_number' => '0948 334 7721', 'email' => 'libertad.barangay@tacloban.gov.ph', 'status' => 'active', 'description' => 'Downtown-adjacent barangay with mixed commercial households.'],
            ['name' => 'Brgy. Old Kawayan', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, Old Kawayan', 'contact_person' => 'Kagawad Cherry Ann Tan', 'contact_number' => '0949 557 3302', 'email' => 'oldkawayan.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Small coastal barangay; feasibility visit for coastal livelihood program.'],
            ['name' => 'Brgy. San Paglaum', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'address' => 'Barangay Hall, San Paglaum', 'contact_person' => 'Capt. Emmanuel Malate', 'contact_number' => '0950 118 4490', 'email' => 'sanpaglaum.barangay@tacloban.gov.ph', 'status' => 'prospecting', 'description' => 'Small relocation-site barangay; community organizing ongoing.'],

            // ---- Palo expansion (verified vs PhilAtlas) ----
            ['name' => 'Brgy. Guindapunan', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Barangay Hall, Guindapunan', 'contact_person' => 'Capt. Joshua Nunez', 'contact_number' => '0951 224 6678', 'email' => 'guindapunan.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Palo\'s most populous barangay; near the Leyte provincial capitol.'],
            ['name' => 'Brgy. Libertad, Palo', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Barangay Hall, Libertad', 'contact_person' => 'Kagawad Mark Joseph Ramirez', 'contact_number' => '0952 339 0012', 'email' => 'libertad-palo.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Growing residential barangay between the capitol and the highway.'],
            ['name' => 'Brgy. Pawing', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Near Daniel Z. Romualdez Airport', 'contact_person' => 'Capt. Daniel Advincula', 'contact_number' => '0953 440 8834', 'email' => 'pawing.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Barangay beside the regional airport; transit and workers\' households.'],
            ['name' => 'Brgy. Gacao', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Barangay Hall, Gacao', 'contact_person' => 'Kagawad Ronald Ramos', 'contact_number' => '0954 551 2245', 'email' => 'gacao.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Upland farming barangay in Palo\'s interior.'],
            ['name' => 'Brgy. Baras', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Coastal road, Baras', 'contact_person' => 'Capt. Maricel Delima', 'contact_number' => '0955 662 9970', 'email' => 'baras.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay with fishing households along San Pedro Bay.'],
            ['name' => 'Brgy. Naga-naga', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Barangay Hall, Naga-naga', 'contact_person' => 'Kagawad Bryan Espina', 'contact_number' => '0956 773 1108', 'email' => 'naganaga.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay south of the poblacion; aquaculture livelihoods.'],
            ['name' => 'Brgy. Cangumbang', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'Barangay Hall, Cangumbang', 'contact_person' => 'Capt. Kenneth Arpon', 'contact_number' => '0957 884 3321', 'email' => 'cangumbang.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay with mangrove-restervation areas and fisherfolk families.'],
            ['name' => 'Brgy. Candahug', 'municipality' => 'Palo', 'province' => 'Leyte', 'address' => 'MacArthur Landing Memorial area', 'contact_person' => 'Kagawad Rose Ann Villanueva', 'contact_number' => '0958 995 4432', 'email' => 'candahug.barangay@palo.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay at the MacArthur Landing Memorial; tourism-adjacent households.'],

            // ---- Tanauan expansion (verified vs PhilAtlas) ----
            ['name' => 'Brgy. Pago', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Pago', 'contact_person' => 'Capt. Patrick Reyes', 'contact_number' => '0959 110 5567', 'email' => 'pago.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Tanauan\'s fastest-growing barangay; many young families.'],
            ['name' => 'Brgy. Canramos', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Poblacion, Canramos', 'contact_person' => 'Kagawad Albert Ibanez', 'contact_number' => '0960 221 6678', 'email' => 'canramos.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Poblacion barangay near the municipal hall and public market.'],
            ['name' => 'Brgy. San Roque, Tanauan', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Poblacion, San Roque', 'contact_person' => 'Capt. Kristine Asis', 'contact_number' => '0961 332 7789', 'email' => 'sanroque-tanauan.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Poblacion barangay along the national highway.'],
            ['name' => 'Brgy. Cabuynan', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Cabuynan', 'contact_person' => 'Kagawad Joel Mercado', 'contact_number' => '0962 443 8890', 'email' => 'cabuynan.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Tanauan\'s largest coastal barangay; fishing and mat-weaving livelihoods.'],
            ['name' => 'Brgy. Sacme', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Sacme', 'contact_person' => 'Capt. Francis Bohol', 'contact_number' => '0963 554 9901', 'email' => 'sacme.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Rapidly growing residential barangay inland of the highway.'],
            ['name' => 'Brgy. Bislig', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Bislig', 'contact_person' => 'Kagawad Gerald Fuentes', 'contact_number' => '0964 665 1123', 'email' => 'bislig.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay with productive mangrove and shellfish areas.'],
            ['name' => 'Brgy. Catmon', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Catmon', 'contact_person' => 'Capt. Mary Jane Omega', 'contact_number' => '0965 776 2234', 'email' => 'catmon.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Agricultural barangay producing rice and root crops.'],
            ['name' => 'Brgy. Malaguicay', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Malaguicay', 'contact_person' => 'Kagawad Adrian Bautista', 'contact_number' => '0966 887 3345', 'email' => 'malaguicay.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay with small-scale fishing households.'],
            ['name' => 'Brgy. Mohon', 'municipality' => 'Tanauan', 'province' => 'Leyte', 'address' => 'Barangay Hall, Mohon', 'contact_person' => 'Capt. Leah Mae Penaranda', 'contact_number' => '0967 998 4456', 'email' => 'mohon.barangay@tanauan.gov.ph', 'status' => 'active', 'description' => 'Coastal barangay known for its beachfront and bolo-making trade.'],

            // ---- Partner schools (type = school) ----
            ['name' => 'Caibaan Elementary School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'elementary', 'address' => 'Caibaan, Tacloban City', 'contact_person' => 'Principal Joseph Rojas', 'contact_number' => '0917 220 3341', 'email' => 'caibaan.es@deped.gov.ph', 'status' => 'active', 'description' => 'Public elementary school in Brgy. Caibaan; prospective literacy-program site.'],
            ['name' => 'Bagacay Elementary School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'elementary', 'address' => 'Bagacay, Tacloban City', 'contact_person' => 'Principal Mark Vincent Silvano', 'contact_number' => '0918 331 4452', 'email' => 'bagacay.es@deped.gov.ph', 'status' => 'active', 'description' => 'Public elementary school serving Brgy. Bagacay households.'],
            ['name' => 'Nula-Tula Elementary School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'elementary', 'address' => 'Nula-tula, Tacloban City', 'contact_person' => 'Principal Carla Mae Yu', 'contact_number' => '0919 442 5563', 'email' => 'nulatula.es@deped.gov.ph', 'status' => 'active', 'description' => 'Public elementary school in Brgy. Nula-tula; reading remediation partner.'],
            ['name' => 'Utap Elementary School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'elementary', 'address' => 'Utap, Tacloban City', 'contact_person' => 'Principal Vincent Go', 'contact_number' => '0920 553 6674', 'email' => 'utap.es@deped.gov.ph', 'status' => 'active', 'description' => 'Public elementary school in Brgy. Utap; northern partner school.'],
            ['name' => 'Anibong Elementary School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'elementary', 'address' => 'Anibong, Tacloban City', 'contact_person' => 'Principal Noel Francisco', 'contact_number' => '0921 664 7785', 'email' => 'anibong.es@deped.gov.ph', 'status' => 'active', 'description' => 'Public elementary school in the Anibong shipyard community.'],
            ['name' => 'Leyte National High School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'secondary', 'address' => 'M.H. del Pilar St., Tacloban City', 'contact_person' => 'Principal Hannah Mae Salazar', 'contact_number' => '0922 775 8896', 'email' => 'lnhs@deped.gov.ph', 'status' => 'active', 'description' => 'The region\'s flagship public secondary school; campus near LNU.'],
            ['name' => 'Sagkahan National High School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'secondary', 'address' => 'Sagkahan, Tacloban City', 'contact_person' => 'Principal Dennis Estrera', 'contact_number' => '0923 886 9907', 'email' => 'sagkahan.nhs@deped.gov.ph', 'status' => 'active', 'description' => 'Public secondary school serving Brgy. Sagkahan and nearby barangays.'],
            ['name' => 'San Jose National High School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'secondary', 'address' => 'San Jose, Tacloban City', 'contact_person' => 'Principal Roberto Romero', 'contact_number' => '0924 997 1108', 'email' => 'sanjose.nhs@deped.gov.ph', 'status' => 'active', 'description' => 'Public secondary school in Brgy. San Jose; LITRAWIYA partner.'],
            ['name' => 'Marasbaras National High School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'secondary', 'address' => 'Marasbaras, Tacloban City', 'contact_person' => 'Principal Aira Mae Medalla', 'contact_number' => '0925 108 2219', 'email' => 'marasbaras.nhs@deped.gov.ph', 'status' => 'active', 'description' => 'Public secondary school in the Marasbaras district.'],
            ['name' => 'Sto. Niño Senior High School', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'secondary', 'address' => 'Santo Niño, Tacloban City', 'contact_person' => 'Principal Josephine Baronda', 'contact_number' => '0926 219 3320', 'email' => 'stonino.shs@deped.gov.ph', 'status' => 'active', 'description' => 'Public senior high school in Brgy. Santo Niño.'],
            ['name' => 'Eastern Visayas State University', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'higher_ed', 'address' => 'Tacloban City, Leyte', 'contact_person' => 'Dr. Reynaldo Dagasdas', 'contact_number' => '0927 330 4431', 'email' => 'info@evsu.edu.ph', 'status' => 'active', 'description' => 'Regional state university; occasional academic extension partner.'],
            ['name' => 'Asian Development Foundation College', 'municipality' => 'Tacloban City', 'province' => 'Leyte', 'type' => 'school', 'school_level' => 'higher_ed', 'address' => 'Tacloban City, Leyte', 'contact_person' => 'Dr. Bryan Daclitan', 'contact_number' => '0928 441 5542', 'email' => 'info@adfc.edu.ph', 'status' => 'active', 'description' => 'Private college in Tacloban; service-learning collaboration partner.'],
        ];

        $ids = [];
        foreach ($rows as $row) {
            $ids[$row['name']] = Community::create($row)->id;
        }
        $this->communityIds = $ids;
    }

    /**
     * The six inherited projects, each tagged with its place in the revised
     * hierarchy (revision §3.1 / Phase R2).
     *
     * The `hierarchy` key is [college code, broad program title]. It is the
     * SAME mapping the R2b migration backfills, kept in sync deliberately: the
     * migration repairs databases that already held rows, while this seeder
     * creates them correctly on `migrate:fresh --seed` (where the migration
     * runs against an empty table and therefore finds nothing to backfill).
     *
     * Codes stay `EXT-{year}-{seq}` — migrated rows keep their history (R-Q4).
     * Only NEW projects created through the UI get college-prefixed codes.
     */
    private function seedPrograms(): void
    {
        $communityIds = $this->communityIds;
        $lead = fn (string $email) => Faculty::whereHas('user', fn ($q) => $q->where('email', $email))->first()->id;

        $rows = [
            ['code' => 'EXT-2026-001', 'title' => 'LITRAWIYA: Barangay Reading Proficiency Program',
                'hierarchy' => ['COE', 'Literacy, Numeracy & Language'],
                'description' => 'Structured remedial reading sessions for elementary pupils.',
                'goals' => 'Raise reading proficiency of Grades 2–4 pupils in Brgy. San Jose through structured remedial reading sessions.',
                'planned_start_date' => '2026-01-20', 'planned_end_date' => '2026-10-30',
                'target_beneficiaries' => 250, 'beneficiary_categories' => ['Pupils', 'Parents'],
                'allocated_budget' => 48000, 'program_lead_id' => $lead('faculty2@lnu.com'),
                'status' => 'ongoing', 'community' => 'Brgy. San Jose',
                'partners' => ['San Jose Elementary School', 'Barangay Council of San Jose']],
            ['code' => 'EXT-2026-002', 'title' => 'HANDA: Disaster Preparedness Training for Coastal Households',
                'hierarchy' => ['CAS', 'Environmental Conservation & Disaster Preparedness'],
                'description' => 'Evacuation planning and first-response skills for coastal households.',
                'planned_start_date' => '2026-02-10', 'planned_end_date' => '2026-09-15',
                'target_beneficiaries' => 180, 'beneficiary_categories' => ['Fisherfolk', 'Vendor', 'Housewife'],
                'allocated_budget' => 62500, 'program_lead_id' => $lead('faculty3@lnu.com'),
                'status' => 'ongoing', 'community' => 'Brgy. El Reposo',
                'partners' => ['Tacloban City DRRMO', 'Barangay Council of El Reposo']],
            ['code' => 'EXT-2026-003', 'title' => 'KABUHIAN: Livelihood Skills Training on Soap & Detergent Making',
                'hierarchy' => ['CME', 'Livelihood, Technical & Business Management'],
                'description' => 'Starter-livelihood skills for unemployed mothers and out-of-school youth.',
                'planned_start_date' => '2026-03-03', 'planned_end_date' => '2026-06-27',
                'target_beneficiaries' => 120, 'beneficiary_categories' => ['Housewife', 'Out-of-School Youth'],
                'allocated_budget' => 35000, 'program_lead_id' => $lead('faculty4@lnu.com'),
                'status' => 'completed', 'community' => 'Brgy. Salvacion',
                'partners' => ['DTI Leyte', 'Tacloban City LGU']],
            ['code' => 'EXT-2026-004', 'title' => 'e-LITERACY: Digital Literacy for Parents & Senior Citizens',
                'hierarchy' => ['CAS', 'Information, Communication & Education'],
                'description' => 'Bridging the digital divide for parents and senior citizens in Sagkahan.',
                'planned_start_date' => '2026-06-08', 'planned_end_date' => '2026-11-28',
                'target_beneficiaries' => 150, 'beneficiary_categories' => ['Parent', 'Senior Citizen'],
                'allocated_budget' => 40000, 'program_lead_id' => $lead('faculty1@lnu.com'),
                'status' => 'ongoing', 'community' => 'Brgy. Sagkahan',
                'partners' => ['Sagkahan Barangay Council']],
            ['code' => 'EXT-2026-005', 'title' => 'SENIOR CARE: Health & Wellness Program for Senior Citizens',
                'hierarchy' => ['CAS', 'Information, Communication & Education'],
                'description' => 'Health literacy and self-care practices among senior citizens in Dulag.',
                'planned_start_date' => '2026-07-13', 'planned_end_date' => '2026-12-12',
                'target_beneficiaries' => 200, 'beneficiary_categories' => ['Senior Citizen'],
                'allocated_budget' => 55000, 'program_lead_id' => $lead('faculty2@lnu.com'),
                'status' => 'ongoing', 'community' => 'Brgy. San Rafael',
                'partners' => ['San Rafael Health Station', 'Dulag OSCA']],
            ['code' => 'EXT-2026-006', 'title' => 'BATANG MATINIK: Sports & Values Formation Clinic',
                'hierarchy' => ['COE', 'Physical Fitness & Sports Development'],
                'description' => 'Sports and discipline for out-of-school youth.',
                'planned_start_date' => '2027-01-11', 'planned_end_date' => '2027-05-29',
                'target_beneficiaries' => 140, 'beneficiary_categories' => ['Out-of-School Youth'],
                'allocated_budget' => 28000, 'program_lead_id' => $lead('faculty4@lnu.com'),
                'status' => 'draft', 'community' => 'Brgy. San Jose',
                'partners' => []],
        ];

        $admin = User::where('email', 'admin@lnu.com')->first();

        /* Resolve the hierarchy once. CollegeSeeder and ProgramSeeder run before
           this one (see DatabaseSeeder), so both lookups are populated. If a
           seeder is run standalone and the tables are empty, the links stay
           null — the R2b migration backfills them, so either order converges. */
        $collegeIds = College::pluck('id', 'code');
        $broadProgramIds = Program::pluck('id', 'title');

        $ids = [];
        foreach ($rows as $row) {
            $communityName = $row['community'];
            [$collegeCode, $broadTitle] = $row['hierarchy'];
            unset($row['community'], $row['hierarchy']);

            $program = ExtensionProject::create([
                ...$row,
                'college_id' => $collegeIds[$collegeCode] ?? null,
                'program_id' => $broadProgramIds[$broadTitle] ?? null,
                'goals' => $row['description'],
                'description' => $row['description'],
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $program->communities()->sync([$communityIds[$communityName]]);
            $ids[$row['code']] = $program->id;
        }
        $this->programIds = $ids;
    }

    private function seedBeneficiaries(): void
    {
        $communityIds = $this->communityIds;
        $programIds = $this->programIds;

        $rows = [
            ['first_name' => 'Lucia', 'middle_name' => 'R.', 'last_name' => 'Amistoso', 'barangay' => 'San Jose', 'sex' => 'Female', 'age' => 41, 'category' => 'Housewife', 'program' => 'EXT-2026-003', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Roberto', 'middle_name' => 'G.', 'last_name' => 'Tan', 'barangay' => 'San Jose', 'sex' => 'Male', 'age' => 37, 'category' => 'Farmer', 'program' => 'EXT-2026-002', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Marilou', 'middle_name' => 'D.', 'last_name' => 'Sabalza', 'barangay' => 'Apitong', 'sex' => 'Female', 'age' => 29, 'category' => 'Fisherfolk', 'program' => 'EXT-2026-001', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Efren', 'middle_name' => 'L.', 'last_name' => 'Bionat', 'barangay' => 'San Rafael', 'sex' => 'Male', 'age' => 63, 'category' => 'Senior Citizen', 'program' => 'EXT-2026-005', 'municipality' => 'Dulag'],
            ['first_name' => 'Jocelyn', 'middle_name' => 'M.', 'last_name' => 'Gorrido', 'barangay' => 'Sagkahan', 'sex' => 'Female', 'age' => 34, 'category' => 'Parent', 'program' => 'EXT-2026-004', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Antonio', 'middle_name' => 'P.', 'last_name' => 'Ybañez', 'barangay' => 'Sagkahan', 'sex' => 'Male', 'age' => 68, 'category' => 'Senior Citizen', 'program' => 'EXT-2026-004', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Rebecca', 'middle_name' => 'S.', 'last_name' => 'Dolina', 'barangay' => 'El Reposo', 'sex' => 'Female', 'age' => 45, 'category' => 'Vendor', 'program' => 'EXT-2026-002', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Nestor', 'middle_name' => 'C.', 'last_name' => 'De Paz', 'barangay' => 'Salvacion', 'sex' => 'Male', 'age' => 52, 'category' => 'Fisherman', 'program' => 'EXT-2026-003', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Analyn', 'middle_name' => 'T.', 'last_name' => 'Catugas', 'barangay' => 'San Jose', 'sex' => 'Female', 'age' => 26, 'category' => 'Out-of-School Youth', 'program' => 'EXT-2026-001', 'municipality' => 'Tacloban City'],
            ['first_name' => 'Rodrigo', 'middle_name' => 'E.', 'last_name' => 'Balila', 'barangay' => 'San Rafael', 'sex' => 'Male', 'age' => 59, 'category' => 'Tricycle Driver', 'program' => 'EXT-2026-005', 'municipality' => 'Dulag'],
            ['first_name' => 'Rosario', 'middle_name' => 'T.', 'last_name' => 'Ebdane', 'barangay' => 'San Jose', 'sex' => 'Female', 'age' => 47, 'category' => 'Barangay Worker', 'program' => null, 'municipality' => 'Tacloban City'],
            ['first_name' => 'Danilo', 'middle_name' => 'P.', 'last_name' => 'Ondoy', 'barangay' => 'Apitong', 'sex' => 'Male', 'age' => 35, 'category' => 'Construction Worker', 'program' => null, 'municipality' => 'Tacloban City'],
        ];

        $enrolled = [];
        foreach ($rows as $row) {
            $programCode = $row['program'];
            unset($row['program']);

            $beneficiary = Beneficiary::create([
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'],
                'last_name' => $row['last_name'],
                'age' => $row['age'],
                'gender' => $row['sex'],
                'phone' => '09123456789',
                'barangay' => $row['barangay'],
                'municipality' => $row['municipality'],
                'province' => 'Leyte',
                'community_id' => $communityIds['Brgy. '.$row['barangay']] ?? null,
                'beneficiary_category' => $row['category'],
                'occupation' => $row['category'],
            ]);

            if ($programCode) {
                DB::table('extension_project_beneficiary')->insert([
                    'extension_project_id' => $programIds[$programCode],
                    'beneficiary_id' => $beneficiary->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $enrolled[$programCode][] = $beneficiary->id;
            }
        }
        $this->enrolledByProgram = $enrolled;
    }

    /**
     * Attendance for completed activities over each program's enrolled
     * beneficiaries (8.2) — gives the 8.6 KPI formulas real data.
     */
    private function seedAttendance(): void
    {
        $statusPlan = ['present', 'present', 'present', 'late', 'present', 'absent', 'present'];

        $activities = Activity::query()->where('status', 'completed')->with('program')->get();
        $i = 0;
        foreach ($activities as $activity) {
            $enrolled = $this->enrolledByProgram[$activity->program->code] ?? [];
            foreach ($enrolled as $beneficiaryId) {
                $status = $statusPlan[$i % count($statusPlan)];
                if ($status !== 'absent') {
                    Attendance::create([
                        'activity_id' => $activity->id,
                        'beneficiary_id' => $beneficiaryId,
                        'attendance_date' => $activity->planned_start_date,
                        'status' => $status,
                    ]);
                }
                $i++;
            }
        }
    }

    private function seedActivities(): void
    {
        $programIds = $this->programIds;
        $communityIds = $this->communityIds;

        $rows = [
            ['program' => 'EXT-2026-001', 'title' => 'Pre-Assessment & Reading Camp Kick-off', 'start' => '2026-02-03', 'end' => '2026-02-03', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'venue' => 'San Jose Elementary School', 'status' => 'completed', 'pre' => 58.2, 'post' => null, 'satisfaction' => 4.2],
            ['program' => 'EXT-2026-001', 'title' => 'Remedial Reading Session Batch 3', 'start' => '2026-07-18', 'end' => '2026-07-18', 'start_time' => '13:00:00', 'end_time' => '16:00:00', 'venue' => 'San Jose Day Care Center', 'status' => 'completed', 'pre' => null, 'post' => 70.6, 'satisfaction' => 4.5],
            ['program' => 'EXT-2026-001', 'title' => 'Mid-Year Reading Proficiency Evaluation', 'start' => '2026-08-22', 'end' => '2026-08-22', 'start_time' => '08:00:00', 'end_time' => '11:00:00', 'venue' => 'San Jose Elementary School', 'status' => 'draft', 'pre' => null, 'post' => null, 'satisfaction' => null],
            ['program' => 'EXT-2026-002', 'title' => 'Typhoon Drill & Evacuation Simulation', 'start' => '2026-06-21', 'end' => '2026-06-21', 'start_time' => '07:00:00', 'end_time' => '12:00:00', 'venue' => 'El Reposo Barangay Hall', 'status' => 'completed', 'pre' => 61.0, 'post' => 72.8, 'satisfaction' => 4.6],
            ['program' => 'EXT-2026-002', 'title' => 'First-Aid & Water Rescue Training', 'start' => '2026-08-09', 'end' => '2026-08-09', 'start_time' => '09:00:00', 'end_time' => '16:00:00', 'venue' => 'Tacloban City Convention Center', 'status' => 'completed', 'pre' => 61.0, 'post' => null, 'satisfaction' => null],
            ['program' => 'EXT-2026-004', 'title' => 'Basic Computer Hands-on Workshop 2', 'start' => '2026-08-16', 'end' => '2026-08-16', 'start_time' => '13:00:00', 'end_time' => '17:00:00', 'venue' => 'Sagkahan Learning Hub', 'status' => 'completed', 'pre' => null, 'post' => null, 'satisfaction' => 4.1],
            ['program' => 'EXT-2026-005', 'title' => 'Blood Pressure Screening & Wellness Talk', 'start' => '2026-08-30', 'end' => '2026-08-30', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'venue' => 'San Rafael Health Station', 'status' => 'ongoing', 'pre' => null, 'post' => null, 'satisfaction' => null],
            ['program' => 'EXT-2026-003', 'title' => 'Post-Training Product Showcase', 'start' => '2026-06-26', 'end' => '2026-06-26', 'start_time' => '10:00:00', 'end_time' => '14:00:00', 'venue' => 'Salvacion Covered Court', 'status' => 'completed', 'pre' => 64.0, 'post' => 75.2, 'satisfaction' => 4.8],
            ['program' => 'EXT-2026-005', 'title' => 'PEACE corners: Youth Conflict Resolution Workshop 1', 'start' => '2026-09-12', 'end' => '2026-09-12', 'start_time' => '13:00:00', 'end_time' => '16:00:00', 'venue' => 'Salvacion Barangay Hall', 'status' => 'draft', 'pre' => null, 'post' => null, 'satisfaction' => null],
        ];

        $facultyByEmail = [
            'EXT-2026-001' => 'faculty2@lnu.com',
            'EXT-2026-002' => 'faculty3@lnu.com',
            'EXT-2026-003' => 'faculty4@lnu.com',
            'EXT-2026-004' => 'faculty1@lnu.com',
            'EXT-2026-005' => 'faculty2@lnu.com',
        ];

        $ids = [];
        foreach ($rows as $row) {
            $programId = $programIds[$row['program']];
            $activity = Activity::create([
                'extension_project_id' => $programId,
                'title' => $row['title'],
                'planned_start_date' => $row['start'],
                'planned_end_date' => $row['end'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'venue' => $row['venue'],
                'status' => $row['status'],
                'pre_assessment_score' => $row['pre'],
                'post_assessment_score' => $row['post'],
                'satisfaction_rating' => $row['satisfaction'],
            ]);

            if (in_array($row['status'], ['completed', 'ongoing', 'draft'])) {
                $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', $facultyByEmail[$row['program']]))->first();
                if ($faculty) {
                    $activity->faculty()->syncWithoutDetaching([$faculty->id]);
                }
            }

            $ids[$row['program'].':'.$row['title']] = $activity->id;
        }
        $this->command->activityIds = $ids;
    }

    private function seedObjectives(): void
    {
        $programIds = $this->programIds;

        $rows = [
            'EXT-2026-001' => [
                ['objective' => 'Enroll Grades 2–4 pupils in structured remedial reading sessions', 'kpi_metric' => 'community_reach', 'baseline' => 0, 'target' => 250, 'unit' => 'pupils', 'target_date' => '2026-10-30', 'evidence' => 'Attendance rosters; mid-year evaluation.'],
                ['objective' => 'Raise reading proficiency from baseline to target', 'kpi' => 'knowledge_gain', 'baseline' => 2.1, 'target' => 3.2, 'unit' => '/5 score', 'target_date' => '2026-10-30', 'evidence' => 'Pre/post reading tests.'],
                ['objective' => 'Sustain ≥80% session attendance consistency', 'kpi' => 'attendance_consistency', 'baseline' => 0, 'target' => 80, 'unit' => '%', 'target_date' => '2026-10-30', 'evidence' => null],
            ],
            'EXT-2026-002' => [
                ['objective' => 'Train coastal households in evacuation & first response', 'kpi' => 'community_reach', 'baseline' => 0, 'target' => 180, 'unit' => 'households', 'target_date' => '2026-09-15', 'evidence' => 'Drill participation logs.'],
                ['objective' => 'Achieve ≥90% budget utilization without overrun', 'kpi' => 'budget_utilization', 'baseline' => 0, 'target' => 90, 'unit' => '%', 'target_date' => '2026-09-15', 'evidence' => 'Finance ledger Q3.'],
            ],
            'EXT-2026-003' => [
                ['objective' => 'Deliver livelihood skills to unemployed mothers & OSY', 'kpi' => 'community_reach', 'baseline' => 0, 'target' => 120, 'unit' => 'persons', 'target_date' => '2026-06-27', 'evidence' => 'Registration + showcase attendance.'],
                ['objective' => 'Improve livelihood confidence from baseline', 'kpi' => 'knowledge_gain', 'baseline' => 1.8, 'target' => 2.6, 'unit' => '/5 score', 'target_date' => '2026-06-27', 'evidence' => 'Pre/post self-assessment.'],
                ['objective' => 'Form a graduate enterprise association within the program period', 'kpi' => null, 'baseline' => null, 'target' => 1, 'unit' => 'association', 'target_date' => '2026-06-27', 'evidence' => 'Association organizing deferred — graduates requested a Q4 schedule.', 'actual' => 0],
            ],
            'EXT-2026-004' => [
                ['objective' => 'Bridge the digital divide for parents & seniors', 'kpi' => 'community_reach', 'baseline' => 0, 'target' => 150, 'unit' => 'persons', 'target_date' => '2026-11-28', 'evidence' => 'Hub sign-in sheets.'],
                ['objective' => 'Achieve ≥20% skill uplift in digital literacy', 'kpi' => 'knowledge_gain', 'baseline' => 1.4, 'target' => 1.9, 'unit' => '/5 score', 'target_date' => '2026-11-28', 'evidence' => 'Pre/post module quizzes.'],
            ],
            'EXT-2026-005' => [
                ['objective' => 'Serve senior citizens through health & wellness sessions', 'kpi' => 'community_reach', 'baseline' => 0, 'target' => 200, 'unit' => 'persons', 'target_date' => '2026-12-12', 'evidence' => 'Health station logs.'],
                ['objective' => 'Improve health literacy self-assessment', 'kpi' => 'knowledge_gain', 'baseline' => 2.0, 'target' => 2.6, 'unit' => '/5 score', 'target_date' => '2026-12-12', 'evidence' => 'Pre/post wellness talk forms.'],
                ['objective' => 'Achieve ≥70% participation rate among enrolled seniors', 'kpi' => 'participation_rate', 'baseline' => 0, 'target' => 70, 'unit' => '%', 'target_date' => '2026-12-12', 'evidence' => 'Attendance vs enrollment roll.'],
            ],
            'EXT-2026-006' => [
                ['objective' => 'Channel youth energy into sports while instilling discipline and teamwork values', 'kpi' => 'community_reach', 'baseline' => 0, 'target' => 140, 'unit' => 'youth', 'target_date' => '2027-05-29', 'evidence' => null],
            ],
        ];

        foreach ($rows as $code => $objectives) {
            foreach ($objectives as $o) {
                ProgramObjective::create([
                    'extension_project_id' => $this->programIds[$code],
                    'objective' => $o['objective'],
                    'kpi_metric' => $o['kpi'] ?? null,
                    'baseline_value' => $o['baseline'] ?? null,
                    'target_value' => $o['target'],
                    'actual_value' => $o['actual'] ?? null,
                    'unit' => $o['unit'],
                    'target_date' => $o['target_date'],
                    'status' => 'not_started',
                    'evidence_notes' => $o['evidence'],
                ]);
            }
        }
    }

    private function seedBudgets(): void
    {
        $programIds = $this->programIds;
        $activityKeys = Activity::query()->pluck('id', 'title');

        $rows = [
            ['program' => 'EXT-2026-001', 'activity' => 'Pre-Assessment & Reading Camp Kick-off', 'item' => 'Reading materials & big books', 'amount' => 12500, 'date' => '2026-02-10', 'ref' => 'REC-2026-0101'],
            ['program' => 'EXT-2026-001', 'activity' => 'Pre-Assessment & Reading Camp Kick-off', 'item' => 'Kick-off snacks & logistics', 'amount' => 6800, 'date' => '2026-02-03'],
            ['program' => 'EXT-2026-001', 'activity' => 'Remedial Reading Session Batch 3', 'item' => 'Session materials — batch 3', 'amount' => 5200, 'date' => '2026-07-18'],
            ['program' => 'EXT-2026-001', 'activity' => null, 'item' => 'Mid-year evaluation printing', 'amount' => 7000, 'date' => '2026-08-21'],
            ['program' => 'EXT-2026-002', 'activity' => 'Typhoon Drill & Evacuation Simulation', 'item' => 'Drill equipment & PPE', 'amount' => 21400, 'date' => '2026-06-21'],
            ['program' => 'EXT-2026-002', 'activity' => 'First-Aid & Water Rescue Training', 'item' => 'First-aid consumables & rescue gear', 'amount' => 18600, 'date' => '2026-08-09'],
            ['program' => 'EXT-2026-002', 'activity' => null, 'item' => 'Venue & transport (overrun entry)', 'amount' => 24500, 'date' => '2026-08-28'],
            ['program' => 'EXT-2026-003', 'activity' => 'Post-Training Product Showcase', 'item' => 'Soap & detergent raw materials', 'amount' => 15200, 'date' => '2026-03-15'],
            ['program' => 'EXT-2026-003', 'activity' => 'Post-Training Product Showcase', 'item' => 'Training kits & packaging supplies', 'amount' => 11400, 'date' => '2026-04-20'],
            ['program' => 'EXT-2026-003', 'activity' => null, 'item' => 'Showcase logistics', 'amount' => 7150, 'date' => '2026-06-26'],
            ['program' => 'EXT-2026-004', 'activity' => 'Basic Computer Hands-on Workshop 2', 'item' => 'Workshop laptops rental & internet', 'amount' => 8600, 'date' => '2026-08-16'],
            ['program' => 'EXT-2026-004', 'activity' => null, 'item' => 'Printed handouts & certificates', 'amount' => 5600, 'date' => '2026-06-08'],
            ['program' => 'EXT-2026-005', 'activity' => 'Blood Pressure Screening & Wellness Talk', 'item' => 'BP apparatus & screening supplies', 'amount' => 12700, 'date' => '2026-08-30'],
            ['program' => 'EXT-2026-005', 'activity' => null, 'item' => 'Wellness talk materials', 'amount' => 8300, 'date' => '2026-07-13'],
        ];

        foreach ($rows as $i => $row) {
            BudgetUtilization::create([
                'extension_project_id' => $programIds[$row['program']],
                'activity_id' => $row['activity'] ? $activityKeys[$row['activity']] ?? null : null,
                'item_name' => $row['item'],
                'amount' => $row['amount'],
                'date_used' => $row['date'],
                'receipt_reference' => 'REC-2026-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            ]);
        }

        $this->command->info('  HANDA deliberately over-allocated by ₱2,000 to demo D7.');
    }

    private function seedNeedsAssessments(): void
    {
        $communityIds = $this->communityIds;
        $secretary = User::where('email', 'secretary@lnu.com')->first();
        $faculty1 = User::where('email', 'faculty1@lnu.com')->first();

        $serviceScale = ['Very poor', 'Poor', 'Fair', 'Good', 'Very good'];

        $respondents = [
            // Brgy. San Jose · Q2 2026 — validated batch (8 respondents)
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Lucia', 'Riza', 'Amistoso'], 'age' => 41, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['4 members'], 'livelihood' => ['Retail / sari-sari store', 'Food vending'],
                'training' => ['Food processing', 'Dressmaking / sewing'], 'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Low income', 'Lack of employment'],
                'water' => ['Deep well'], 'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Roberto', 'G.', 'Tan'], 'age' => 37, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['High School Graduate'], 'family' => ['5 members'], 'livelihood' => ['Farming'], 'training' => ['Vegetable production'],
                'eduInterest' => ['Agriculture training'], 'problems' => ['Low income'], 'water' => ['Deep well'], 'house' => ['Semi-concrete'],
                'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'No', 'reason' => 'Work schedule conflict'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Analyn', 'T.', 'Catugas'], 'age' => 26, 'civil' => 'Single', 'sex' => 'Female', 'religion' => 'Iglesia ni Cristo',
                'education' => ['Senior High School Graduate'], 'family' => ['3 members'], 'livelihood' => ['Unemployed'], 'training' => ['Computer literacy'],
                'eduInterest' => ['Computer literacy', 'TESDA skills'], 'problems' => ['Lack of employment', 'Low income'], 'water' => ['Level II / piped'],
                'house' => ['Wooden house'], 'electricity' => 'Yes', 'rating' => 'Fair', 'available' => 'Yes'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Rosario', 'T.', 'Ebdane'], 'age' => 47, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['College Graduate'], 'family' => ['6 members'], 'livelihood' => ['Home-based income'], 'training' => ['Food processing'],
                'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Education expenses'], 'water' => ['Level II / piped'],
                'house' => ['Concrete / solid house'], 'electricity' => 'Yes', 'rating' => 'Very good', 'available' => 'Yes'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Miguel', 'P.', 'Dela Cruz'], 'age' => 52, 'civil' => 'Widowed', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Undergraduate'], 'family' => ['2 members'], 'livelihood' => ['Fishing'], 'training' => ['Fish processing'],
                'eduInterest' => ['Literacy'], 'problems' => ['Insufficient food', 'Low income'], 'water' => ['Deep well', 'Bottled water'],
                'house' => ['Bamboo / nipa'], 'electricity' => 'No', 'lighting' => ['Kerosene lamp'], 'rating' => 'Poor', 'available' => 'Yes'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Carmen', 'S.', 'Reyes'], 'age' => 63, 'civil' => 'Widowed', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Junior High School Graduate'], 'family' => ['4 members'], 'livelihood' => ['Home-based income'], 'training' => ['Beauty care'],
                'eduInterest' => ['Beauty care'], 'problems' => ['Medical expenses'], 'water' => ['Community water system'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'No', 'reason' => 'Health reasons'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Jose', 'M.', 'Gutierrez'], 'age' => 33, 'civil' => 'Live-in', 'sex' => 'Male', 'religion' => 'Born Again / Christian',
                'education' => ['College Undergraduate'], 'family' => ['5 members'], 'livelihood' => ['Construction work'], 'training' => ['Handicraft making'],
                'eduInterest' => ['TESDA skills'], 'problems' => ['Low income', 'Debt'], 'water' => ['Community water system'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. San Jose', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Maria', 'L.', 'Flores'], 'age' => 29, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Islam',
                'education' => ['Junior High School Graduate'], 'family' => ['6 members'], 'livelihood' => ['Food vending'], 'training' => ['Food processing'],
                'eduInterest' => ['Literacy'], 'problems' => ['Medical expenses', 'Low income'], 'water' => ['Deep well'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Fair', 'available' => 'Yes'],

            // Brgy. El Reposo · Q1 2026 — validated batch (5)
            ['community' => 'Brgy. El Reposo', 'quarter' => 1, 'year' => 2026, 'review' => 'validated',
                'name' => ['Rebecca', 'S.', 'Dolina'], 'age' => 45, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Junior High School Graduate'], 'family' => ['6 members'], 'livelihood' => ['Retail / sari-sari store'],
                'training' => ['Household enterprise'], 'eduInterest' => ['Livelihood entrepreneurship'],
                'problems' => ['No potable water', 'No flood control'], 'water' => ['Level II / piped'], 'house' => ['Semi-concrete'],
                'electricity' => 'Yes', 'rating' => 'Very good', 'available' => 'Yes'],
            ['community' => 'Brgy. El Reposo', 'quarter' => 1, 'year' => 2026, 'review' => 'validated',
                'name' => ['Nestor', 'C.', 'De Paz'], 'age' => 44, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['7 members'], 'livelihood' => ['Fishing'], 'training' => ['Fish processing'],
                'eduInterest' => ['Agriculture training'], 'problems' => ['No flood control', 'Low income'], 'water' => ['Deep well'],
                'house' => ['Wooden house'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. El Reposo', 'quarter' => 1, 'year' => 2026, 'review' => 'validated',
                'name' => ['Lourdes', 'B.', 'Amodia'], 'age' => 55, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['5 members'], 'livelihood' => ['Agriculture'], 'training' => ['Vegetable production'],
                'eduInterest' => ['Literacy'], 'problems' => ['No potable water'], 'water' => ['Deep well', 'Rainwater'],
                'house' => ['Semi-concrete'], 'electricity' => 'No', 'lighting' => ['Solar lamp'], 'rating' => 'Fair', 'available' => 'Yes'],
            ['community' => 'Brgy. El Reposo', 'quarter' => 1, 'year' => 2026, 'review' => 'validated',
                'name' => ['Efren', 'D.', 'Lumbre'], 'age' => 61, 'civil' => 'Widowed', 'sex' => 'Male', 'religion' => 'Born Again / Christian',
                'education' => ['High School Graduate'], 'family' => ['2 members'], 'livelihood' => ['Skilled labor'], 'training' => ['Livestock raising'],
                'eduInterest' => ['Basic education'], 'problems' => ['Medical expenses'], 'water' => ['Community water system'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. El Reposo', 'quarter' => 1, 'year' => 2026, 'review' => 'validated',
                'name' => ['Divina', 'O.', 'Alcoy'], 'age' => 38, 'civil' => 'Separated', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Vocational / Technical'], 'family' => ['4 members'], 'livelihood' => ['Home-based income'], 'training' => ['Dressmaking / sewing'],
                'eduInterest' => ['Tailoring / sewing'], 'problems' => ['Lack of employment'], 'water' => ['Level II / piped'],
                'house' => ['Concrete / solid house'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],

            // Brgy. Salvacion · Q2 2026 — validated (5)
            ['community' => 'Brgy. Salvacion', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Lucia', 'R.', 'Amistoso'], 'age' => 41, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['4 members'], 'livelihood' => ['Retail / sari-sari store'],
                'training' => ['Soap making'], 'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Lack of employment'],
                'water' => ['Deep well'], 'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Very good', 'available' => 'Yes'],
            ['community' => 'Brgy. Salvacion', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Nestor', 'C.', 'De Paz'], 'age' => 52, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['6 members'], 'livelihood' => ['Fishing'], 'training' => ['Fish processing'],
                'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Insufficient food', 'Debt'], 'water' => ['River / stream'],
                'house' => ['Bamboo / nipa'], 'electricity' => 'No', 'lighting' => ['Kerosene lamp'], 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. Salvacion', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Sofia', 'R.', 'Bionat'], 'age' => 35, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Senior High School Graduate'], 'family' => ['5 members'], 'livelihood' => ['Unemployed'], 'training' => ['Soap making'],
                'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Lack of employment', 'Debt'], 'water' => ['Deep well'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. Salvacion', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Pedro', 'T.', 'Cabalquinto'], 'age' => 48, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Junior High School Undergraduate'], 'family' => ['7 members'], 'livelihood' => ['Farming'], 'training' => ['Rice/corn farming'],
                'eduInterest' => ['Agriculture training'], 'problems' => ['Debt', 'Low income'], 'water' => ['Deep well'],
                'house' => ['Bamboo / nipa'], 'electricity' => 'No', 'lighting' => ['Kerosene lamp', 'Candles'], 'rating' => 'Fair', 'available' => 'Yes'],
            ['community' => 'Brgy. Salvacion', 'quarter' => 2, 'year' => 2026, 'review' => 'validated',
                'name' => ['Ana', 'G.', 'Salazar'], 'age' => 31, 'civil' => 'Single', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['College Undergraduate'], 'family' => ['3 members'], 'livelihood' => ['OFW family remittance'],
                'training' => ['Household enterprise'], 'eduInterest' => ['Computer literacy'], 'problems' => ['Lack of capital'],
                'water' => ['Level II / piped'], 'house' => ['Concrete / solid house'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],

            // Brgy. Sagkahan · Q2 2026 — pending (3)
            ['community' => 'Brgy. Sagkahan', 'quarter' => 2, 'year' => 2026, 'review' => 'pending',
                'name' => ['Jocelyn', 'M.', 'Gorrido'], 'age' => 34, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Senior High School Graduate'], 'family' => ['5 members'], 'livelihood' => ['Home-based income'],
                'training' => ['Computer literacy'], 'eduInterest' => ['Computer literacy'], 'problems' => ['Low income'],
                'water' => ['Level II / piped'], 'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. Sagkahan', 'quarter' => 2, 'year' => 2026, 'review' => 'pending',
                'name' => ['Antonio', 'P.', 'Ybañez'], 'age' => 68, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['3 members'], 'livelihood' => ['Unemployed'], 'training' => ['Computer literacy'],
                'eduInterest' => ['Basic education'], 'problems' => ['Medical expenses'], 'water' => ['Community water system'],
                'house' => ['Wooden house'], 'electricity' => 'Yes', 'rating' => 'Fair', 'available' => 'Yes'],
            ['community' => 'Brgy. Sagkahan', 'quarter' => 2, 'year' => 2026, 'review' => 'pending',
                'name' => ['Rosa', 'M.', 'Padilla'], 'age' => 44, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Iglesia ni Cristo',
                'education' => ['Junior High School Graduate'], 'family' => ['5 members'], 'livelihood' => ['Retail / sari-sari store'],
                'training' => ['Food processing'], 'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Low income'],
                'water' => ['Level II / piped'], 'house' => ['Concrete / solid house'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],

            // Brgy. San Rafael · Q2 2026 — returned with remarks (2)
            ['community' => 'Brgy. San Rafael', 'quarter' => 2, 'year' => 2026, 'review' => 'returned', 'remarks' => 'Section V incomplete for some respondents — please encode toilet type before resubmission.',
                'name' => ['Efren', 'L.', 'Bionat'], 'age' => 63, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Elementary Graduate'], 'family' => ['4 members'], 'livelihood' => ['Farming'], 'training' => ['Livestock raising'],
                'eduInterest' => ['Agriculture training'], 'problems' => ['Medical expenses'], 'water' => ['Deep well'],
                'house' => ['Semi-concrete'], 'electricity' => 'Yes', 'rating' => 'Good', 'available' => 'Yes'],
            ['community' => 'Brgy. San Rafael', 'quarter' => 2, 'year' => 2026, 'review' => 'returned', 'remarks' => 'Section V incomplete for some respondents — please encode toilet type before resubmission.',
                'name' => ['Rodrigo', 'E.', 'Balila'], 'age' => 59, 'civil' => 'Married', 'sex' => 'Male', 'religion' => 'Roman Catholic',
                'education' => ['Junior High School Graduate'], 'family' => ['5 members'], 'livelihood' => ['Transportation'], 'training' => ['Livestock raising'],
                'eduInterest' => ['TESDA skills'], 'problems' => ['Low income'], 'water' => ['Deep well'],
                'house' => ['Wooden house'], 'electricity' => 'Yes', 'rating' => 'Fair', 'available' => 'Yes'],

            // Brgy. Apitong · Q3 2026 — pending (1, prospecting visit)
            ['community' => 'Brgy. Apitong', 'quarter' => 3, 'year' => 2026, 'review' => 'pending',
                'name' => ['Marilou', 'D.', 'Sabalza'], 'age' => 29, 'civil' => 'Married', 'sex' => 'Female', 'religion' => 'Roman Catholic',
                'education' => ['Junior High School Graduate'], 'family' => ['4 members'], 'livelihood' => ['Fishing'], 'training' => ['Fish processing'],
                'eduInterest' => ['Livelihood entrepreneurship'], 'problems' => ['Lack of employment', 'Low income'], 'water' => ['Deep well'],
                'house' => ['Bamboo / nipa'], 'electricity' => 'No', 'lighting' => ['Kerosene lamp'], 'rating' => 'Poor', 'available' => 'Yes'],
        ];

        foreach ($respondents as $r) {
            $hasLight = $r['electricity'] === 'No';

            // v4.9 vocabulary remaps for legacy demo values.
            $educationRemap = ['High School Graduate' => 'Junior High School Graduate'];
            $religionRemap = ['Born Again / Christian' => 'Evangelical Christianity'];
            $civilRemap = ['Live-in' => 'Single'];

            $education = $r['education'][0] ?? null;
            $education = $educationRemap[$education] ?? $education;
            $religion = $religionRemap[$r['religion']] ?? $r['religion'];
            $civil = $civilRemap[$r['civil']] ?? $r['civil'];

            $familyProblems = array_values(array_intersect($r['problems'], config('smartcemes.vocab.family_problems')));
            $economicProblems = array_values(array_intersect($r['problems'], config('smartcemes.vocab.economic_problems')));
            $infrastructureProblems = array_values(array_intersect($r['problems'], config('smartcemes.vocab.infrastructure_problems')));

            NeedsAssessment::create([
                'community_id' => $communityIds[$r['community']],
                'quarter' => $r['quarter'],
                'year' => $r['year'],
                'uploaded_by' => $r['review'] === 'validated' ? $secretary->id : $faculty1->id,
                'review_status' => $r['review'],
                'reviewed_by' => $r['review'] === 'pending' ? null : $secretary->id,
                'reviewed_at' => $r['review'] === 'pending' ? null : now(),
                'review_remarks' => $r['remarks'] ?? null,
                'respondent_first_name' => $r['name'][0],
                'respondent_middle_name' => $r['name'][1],
                'respondent_last_name' => $r['name'][2],
                'respondent_age' => $r['age'],
                'respondent_civil_status' => $civil,
                'respondent_sex' => $r['sex'],
                'respondent_religion' => $religion,
                'respondent_educational_attainment' => $education,
                'family_composition' => $r['family'][0] ?? null,
                'livelihood_options' => $r['livelihood'][0] ?? null,
                'desired_training' => $r['training'][0] ?? null,
                'barangay_educational_facilities' => ['Elementary school'],
                'household_member_currently_studying' => 'Yes',
                'interested_in_continuing_studies' => 'Yes',
                'areas_of_educational_interest' => $r['eduInterest'][0] ?? null,
                'preferred_training_time' => 'Morning 8:00-12:00',
                'preferred_training_days' => ['Saturday'],
                'common_illnesses' => $r['age'] > 50 ? 'Hypertension' : 'Cough / colds',
                'action_when_sick' => 'Consult barangay health worker',
                'barangay_medical_supplies_available' => ['First aid kit'],
                'has_barangay_health_programs' => 'Yes',
                'benefits_from_barangay_programs' => 'Yes',
                'programs_benefited_from' => ['Health education'],
                'water_source' => $r['water'][0] ?? null,
                'water_source_distance' => 'Just outside',
                'garbage_disposal_method' => 'Collected by barangay',
                'has_own_toilet' => 'Yes',
                'toilet_type' => 'Pour flush',
                'keeps_animals' => 'Yes',
                'animals_kept' => ['Chicken'],
                'house_type' => $r['house'][0] ?? null,
                'tenure_status' => 'Owner',
                'has_electricity' => $r['electricity'],
                'light_source_without_power' => $hasLight ? ($r['lighting'][0] ?? 'Kerosene lamp') : null,
                'appliances_owned' => $r['electricity'] === 'Yes' ? ['Television', 'Cellphone', 'Electric fan'] : ['Cellphone'],
                'barangay_recreational_facilities' => ['Basketball court'],
                'use_of_free_time' => ['Household chores', 'Watching TV'],
                'member_of_organization' => 'Yes',
                'organization_types' => 'Women organization',
                'organization_meeting_frequency' => 'Monthly',
                'organization_usual_activities' => 'Meetings',
                'household_members_in_organization' => '1 member',
                'position_in_organization' => 'Member',
                'family_problems' => $familyProblems,
                'health_problems' => $r['age'] > 50 ? ['High blood pressure'] : [],
                'educational_problems' => [],
                'employment_problems' => in_array('Lack of employment', $r['problems']) ? ['Lack of jobs'] : [],
                'infrastructure_problems' => $infrastructureProblems,
                'economic_problems' => $economicProblems,
                'security_problems' => [],
                'barangay_service_ratings' => ['overall' => $r['rating']],
                'general_feedback' => 'Community response from '.$r['community'].' household visit.',
                'available_for_training' => $r['available'],
                'reason_not_available' => $r['reason'] ?? null,
            ]);
        }

        $this->command->info('  Needs assessments seeded — summaries recomputed automatically.');
    }
}
