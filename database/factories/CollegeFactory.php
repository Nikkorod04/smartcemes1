<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollegeFactory extends Factory
{
    protected $model = College::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('C??'));

        return [
            'code' => $code,
            'name' => 'College of '.fake()->words(2, true),
            'short_name' => $code,
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
