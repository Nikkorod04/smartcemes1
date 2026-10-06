<?php

namespace Database\Factories;

use App\Models\InteragencyAgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the R6 interagency catalogue.
 *
 * The default state is a generic-but-plausible agency so a test that only needs
 * "some agency exists" does not have to state the whole row. Tests that care
 * about a specific referral should pin `agency_code` explicitly, because the
 * code is what `PromptV2` and the validator key on.
 */
class InteragencyAgencyFactory extends Factory
{
    protected $model = InteragencyAgency::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('AG?'));

        return [
            'agency_code' => $code,
            'agency_name' => 'Agency for '.fake()->words(3, true),
            'mandate' => fake()->sentence(4),
            'need_category' => fake()->words(3, true),
            'sample_service' => implode(', ', fake()->words(3)),
            'contact_info' => null,
            'active' => true,
            'sort_order' => 0,
        ];
    }

    /** A retired agency — must not be citable by the prompt. */
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    /** Pin the code so a test can assert a referral resolves to it. */
    public function withCode(string $code, ?string $name = null): static
    {
        return $this->state(fn () => [
            'agency_code' => strtoupper($code),
            'agency_name' => $name ?? ('Department of '.ucfirst(strtolower($code))),
        ]);
    }
}
