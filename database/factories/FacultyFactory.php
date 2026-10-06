<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Faculty factory (added Phase R3 — the Faculty Management module needed one;
 * earlier tests built faculty rows through the seeders instead).
 */
class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->faculty(),
            'employee_id' => 'LNU-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'college_id' => null,
            'department' => fake()->randomElement([
                'College of Arts and Sciences',
                'College of Education',
                'College of Management and Entrepreneurship',
            ]),
            'specialization' => fake()->words(2, true),
            'position' => fake()->randomElement([
                'Instructor I', 'Instructor II', 'Instructor III',
                'Assistant Professor I', 'Assistant Professor II',
                'Associate Professor I', 'Professor I',
            ]),
            'phone' => '09'.fake()->numerify('## ### ####'),
            'address' => fake()->city().', Leyte',
            'status' => Faculty::STATUS_ACTIVE,
        ];
    }

    public function onLeave(): static
    {
        return $this->state(fn () => ['status' => Faculty::STATUS_ON_LEAVE]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Faculty::STATUS_INACTIVE]);
    }

    public function forCollege(College $college): static
    {
        return $this->state(fn () => [
            'college_id' => $college->id,
            'department' => $college->name,
        ]);
    }
}
