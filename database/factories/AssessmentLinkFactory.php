<?php

namespace Database\Factories;

use App\Models\AssessmentLink;
use App\Models\Community;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentLinkFactory extends Factory
{
    protected $model = AssessmentLink::class;

    public function definition(): array
    {
        return [
            'community_id' => Community::factory(),
            'quarter' => 4,
            'year' => (int) now()->format('Y'),
            'token' => AssessmentLink::newToken(),
            'expires_at' => now()->addDays(AssessmentLink::DEFAULT_DAYS)->endOfDay(),
            'created_by' => null,
            'revoked_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }

    public function forPeriod(int $quarter, int $year): static
    {
        return $this->state(fn () => ['quarter' => $quarter, 'year' => $year]);
    }
}
