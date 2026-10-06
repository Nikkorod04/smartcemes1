<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'code' => 'PROG-'.now()->format('Y').'-'.fake()->unique()->numerify('###'),
            'title' => fake()->unique()->words(3, true),
            'pillar' => fake()->randomElement(['social', 'economic', 'environmental']),
            'ceso_thrust' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function social(): static
    {
        return $this->state(fn () => ['pillar' => 'social']);
    }
}
