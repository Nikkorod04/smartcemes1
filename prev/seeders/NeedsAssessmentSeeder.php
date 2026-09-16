<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Database\Seeder;

class NeedsAssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds with comprehensive sample assessments.
     */
    public function run(): void
    {
        $secretary = User::where('role', 'secretary')->first();
        $anibong = Community::firstOrCreate(['name' => 'Anibong']);

        if (! $anibong) {
            return; // Skip if Anibong community doesn't exist
        }

        // Sample respondent data for variety
        $firstNames = ['Maria', 'Juan', 'Rosa', 'Antonio', 'Lucia', 'Pedro', 'Ana', 'Jose', 'Carmen', 'Miguel', 'Isabella', 'Diego', 'Sofia', 'Ricardo', 'Magdalena', 'Fernando', 'Emilia', 'Bernard', 'Patricia', 'Alberto'];
        $lastNames = ['Santos', 'Dela Cruz', 'Reyes', 'Gonzales', 'Fernandez', 'Lopez', 'Rivera', 'Ramirez', 'Torres', 'Cruz', 'Morales', 'Gutierrez', 'Rojas', 'Vargas', 'Castro', 'Medina', 'Flores', 'Silva', 'Rios', 'Soto'];

        $assessments = array_merge(
            $this->generateAssessments($anibong, $secretary, 'Q1', 2026, 1),
            $this->generateAssessments($anibong, $secretary, 'Q2', 2026, 21)
        );

        foreach ($assessments as $assessment) {
            NeedsAssessment::create($assessment);
        }
    }

    private function generateAssessments($community, $secretary, $quarter, $year, $startId): array
    {
        $firstNames = ['Maria', 'Juan', 'Rosa', 'Antonio', 'Lucia', 'Pedro', 'Ana', 'Jose', 'Carmen', 'Miguel', 'Isabella', 'Diego', 'Sofia', 'Ricardo', 'Magdalena', 'Fernando', 'Emilia', 'Bernard', 'Patricia', 'Alberto'];
        $lastNames = ['Santos', 'Dela Cruz', 'Reyes', 'Gonzales', 'Fernandez', 'Lopez', 'Rivera', 'Ramirez', 'Torres', 'Cruz', 'Morales', 'Gutierrez', 'Rojas', 'Vargas', 'Castro', 'Medina', 'Flores', 'Silva', 'Rios', 'Soto'];
        $middleNames = ['Cruz', 'Reyes', 'Lopez', 'Garcia', 'Torres', 'Rodriguez', 'Martinez', 'Hernandez', 'Gonzalez', null];

        $assessments = [];

        for ($i = 0; $i < 20; $i++) {
            $age = rand(18, 75);
            $hasProblems = rand(0, 3); // Distribute problem levels
            $education = $age < 25 ? 'High School' : ['Elementary', 'High School', 'College'][rand(0, 2)];

            $assessment = [
                'community_id' => $community->id,
                'quarter' => $quarter,
                'year' => $year,
                'respondent_first_name' => $firstNames[($startId + $i - 1) % count($firstNames)],
                'respondent_middle_name' => $middleNames[rand(0, count($middleNames) - 1)],
                'respondent_last_name' => $lastNames[($startId + $i + 5) % count($lastNames)],
                'respondent_age' => $age,
                'respondent_civil_status' => $age < 25 ? 'Single' : ['Single', 'Married', 'Widowed'][rand(0, 2)],
                'respondent_sex' => rand(0, 1) ? 'Male' : 'Female',
                'respondent_religion' => ['Catholic', 'Christian', 'Muslim'][rand(0, 2)],
                'respondent_educational_attainment' => json_encode([$education]),
                'family_composition' => json_encode([rand(2, 8).' members']),
                'barangay_educational_facilities' => json_encode(['Elementary Primary', 'Secondary']),
                'livelihood_options' => json_encode($this->getLivelihoods($age)),
                'interested_in_livelihood_training' => rand(0, 1) ? 'Yes' : 'No',
                'areas_of_educational_interest' => json_encode($this->getEducationalInterests()),
                'preferred_training_time' => $hasProblems >= 2 ? 'Morning 8-12' : null,
                'preferred_training_days' => $hasProblems >= 2 ? json_encode(['Wednesday', 'Saturday']) : json_encode([]),
                'desired_training' => $hasProblems >= 2 ? json_encode($this->getTraining()) : json_encode([]),
                'common_illnesses' => json_encode($this->getIllnesses($age, $hasProblems)),
                'action_when_sick' => json_encode($this->getHealthAction()),
                'barangay_medical_supplies_available' => json_encode(['Health Center']),
                'has_barangay_health_programs' => 'Yes',
                'benefits_from_barangay_programs' => rand(0, 1) ? 'Yes' : 'No',
                'programs_benefited_from' => json_encode(['Free Consultation']),
                'water_source' => json_encode($this->getWaterSource()),
                'water_source_distance' => ['Just outside', '250 meters away'][rand(0, 1)],
                'garbage_disposal_method' => json_encode($hasProblems >= 2 ? ['Anywhere'] : ['Compost pit']),
                'household_member_currently_studying' => rand(0, 1) ? 'Yes' : 'No',
                'interested_in_continuing_studies' => rand(0, 1) ? 'Yes' : 'No',
                'has_own_toilet' => $hasProblems >= 2 ? 'No' : 'Yes',
                'toilet_type' => json_encode($hasProblems >= 2 ? ['Antipolo style'] : ['Flush toilet']),
                'keeps_animals' => rand(0, 1) ? 'Yes' : 'No',
                'animals_kept' => json_encode($this->getAnimals()),
                'house_type' => json_encode($this->getHouseType($hasProblems)),
                'tenure_status' => json_encode(['Own house/land']),
                'has_electricity' => $hasProblems >= 3 ? 'No' : 'Yes',
                'light_source_without_power' => json_encode($hasProblems >= 3 ? ['Oil lamp', 'Candle'] : []),
                'appliances_owned' => json_encode($this->getAppliances($hasProblems)),
                'barangay_recreational_facilities' => json_encode(['Basketball Court', 'Volleyball Court']),
                'use_of_free_time' => json_encode(['Playing basketball', 'Watching TV']),
                'member_of_organization' => rand(0, 1) ? 'Yes' : 'No',
                'organization_types' => json_encode($this->getOrganizations()),
                'organization_meeting_frequency' => rand(0, 1) ? 'Monthly' : 'Weekly',
                'organization_usual_activities' => 'Community activities',
                'household_members_in_organization' => json_encode(['Self']),
                'position_in_organization' => ['Member', 'Treasurer', 'Secretary'][rand(0, 2)],
                'family_problems' => json_encode($this->getFamilyProblems($hasProblems)),
                'health_problems' => json_encode($this->getHealthProblems($age, $hasProblems)),
                'educational_problems' => json_encode($hasProblems >= 1 ? ['Far school'] : []),
                'employment_problems' => json_encode($hasProblems >= 1 ? ['Lack of skills'] : []),
                'infrastructure_problems' => json_encode($this->getInfrastructureProblems($hasProblems)),
                'economic_problems' => json_encode($hasProblems >= 2 ? ['Low income', 'No capital'] : []),
                'security_problems' => json_encode($hasProblems >= 3 ? ['No police presence'] : []),
                'barangay_service_ratings' => json_encode(['Law Enforcement' => rand(1, 5), 'Health Service' => rand(3, 5), 'Education Service' => rand(2, 4)]),
                'general_feedback' => $this->getFeedback($hasProblems),
                'available_for_training' => $hasProblems >= 2 ? 'Yes' : 'No',
                'reason_not_available' => $hasProblems < 2 ? 'Work schedule' : null,
                'uploaded_by' => $secretary?->id ?? 1,
            ];

            $assessments[] = $assessment;
        }

        return $assessments;
    }

    private function getLivelihoods($age): array
    {
        $all = ['Farming', 'Fishing', 'Service Work', 'Selling', 'Driving', 'Construction'];

        return array_slice($all, 0, rand(1, 2));
    }

    private function getEducationalInterests(): array
    {
        $all = ['Math', 'English', 'Science', 'Agricultural Issues', 'Health'];

        return array_slice($all, 0, rand(1, 3));
    }

    private function getTraining(): array
    {
        $all = ['Food Processing', 'Animal Husbandry', 'Vegetable Farming', 'Dress Making', 'Computer'];

        return array_slice($all, 0, rand(1, 2));
    }

    private function getIllnesses($age, $severity): array
    {
        if ($severity >= 3) {
            return ['Diabetes', 'Hypertension', 'Arthritis'];
        } elseif ($severity >= 2) {
            return ['Colds', 'Headache', 'Cough'];
        }

        return [];
    }

    private function getHealthAction(): array
    {
        $actions = ['Hospital/Health Center', 'Herbal Medicine', 'Home Remedy'];

        return array_slice($actions, 0, rand(1, 2));
    }

    private function getWaterSource(): array
    {
        $sources = ['NAWASA', 'Deep Well', 'Spring Water'];

        return array_slice($sources, 0, rand(1, 2));
    }

    private function getAnimals(): array
    {
        $all = ['Chicken', 'Duck', 'Pig', 'Goat', 'Cat'];

        return rand(0, 1) ? array_slice($all, 0, rand(1, 2)) : [];
    }

    private function getHouseType($severity): array
    {
        if ($severity >= 2) {
            return ['Nipa/Bamboo'];
        } elseif ($severity >= 1) {
            return ['Half concrete/wood'];
        }

        return ['All concrete'];
    }

    private function getAppliances($severity): array
    {
        if ($severity >= 2) {
            return ['Radio'];
        } elseif ($severity >= 1) {
            return ['TV', 'Electric Fan'];
        }

        return ['TV', 'Refrigerator', 'Electric Fan', 'Washing Machine'];
    }

    private function getOrganizations(): array
    {
        $orgs = ['Civic', 'Religious', 'Agricultural', 'Sports'];

        return rand(0, 1) ? array_slice($orgs, 0, rand(1, 2)) : [];
    }

    private function getFamilyProblems($severity): array
    {
        if ($severity >= 2) {
            return ['Low income', 'Cannot support needs'];
        } elseif ($severity >= 1) {
            return ['Low income'];
        }

        return [];
    }

    private function getHealthProblems($age, $severity): array
    {
        if ($age > 50 && $severity >= 2) {
            return ['Arthritis', 'Hypertension'];
        } elseif ($severity >= 2) {
            return ['Malnourished'];
        }

        return [];
    }

    private function getInfrastructureProblems($severity): array
    {
        if ($severity >= 2) {
            return ['Difficult roads', 'No electricity'];
        } elseif ($severity >= 1) {
            return ['Difficult roads'];
        }

        return [];
    }

    private function getFeedback($severity): string
    {
        if ($severity >= 3) {
            return 'Priority: basic utilities and livelihood support needed.';
        } elseif ($severity >= 2) {
            return 'Community needs training and infrastructure development.';
        } elseif ($severity >= 1) {
            return 'Minor needs, mostly livelihood and skills training.';
        }

        return 'Community doing well, ready for development initiatives.';
    }
}
