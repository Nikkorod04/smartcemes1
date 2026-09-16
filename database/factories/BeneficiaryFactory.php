<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->lastName(),
            'last_name' => fake()->lastName(),
            'age' => fake()->numberBetween(18, 75),
            'gender' => fake()->randomElement(['Female', 'Male']),
            'phone' => '09123456789',
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban City',
            'province' => 'Leyte',
            'beneficiary_category' => 'Housewife',
        ];
    }
}
