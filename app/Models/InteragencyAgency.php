<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An interagency referral partner (Phase R6 / D-R10, §4.6).
 *
 * THE POINT OF THIS MODEL
 * -----------------------
 * CESO's mandate is community TRAINING — the six thrusts in §3. A barangay
 * survey will surface needs that CESO should not attempt to deliver: feeding
 * programmes, medical and dental missions, roads, drainage, water systems. Those
 * are real needs, so they cannot be silently dropped; but presenting them as
 * things CESO should do would misstate CESO's scope (the adviser flagged exactly
 * this — feedback #8).
 *
 * This table is therefore the **closed vocabulary** the AI may cite when handing
 * a need off. `PromptV2` injects the active rows, and a referral that names an
 * agency outside the catalogue is treated as invalid — see
 * `AssessmentAnalysisService::referralsFrom()`. That is what makes each referral
 * defensible: the agency is not the model's invention.
 *
 * WHY `agency_code` IS THE KEY
 * ----------------------------
 * The model returns both a code and a name. The code is what the system resolves
 * against; the name is echoed for readability. Validating on the code means a
 * model that paraphrases "Department of Social Welfare and Development" cannot
 * smuggle in an agency that is not in the catalogue.
 */
class InteragencyAgency extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'interagency_agencies';

    protected $fillable = [
        'agency_code',
        'agency_name',
        'mandate',
        'need_category',
        'sample_service',
        'contact_info',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Interagency agency {$eventName}");
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */

    /**
     * The citable set — what the AI prompt is allowed to reference.
     *
     * Retired agencies are excluded here so removing one from the catalogue
     * takes effect on the next analysis without deleting the history that
     * cites it.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Catalogue presentation order: curated `sort_order`, then name. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('agency_name');
    }

    /* ------------------------------------------------------------------ */
    /* Lookups */
    /* ------------------------------------------------------------------ */

    /**
     * Resolve a model-returned agency code to a catalogue row.
     *
     * Case-insensitive and whitespace-tolerant because the value comes back
     * from a language model; the code is what must match, not its casing.
     * Returns NULL when the code is not in the catalogue — the caller treats
     * that as an invalid referral rather than trusting the model's prose.
     */
    public static function resolve(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return static::withTrashed()->where('agency_code', $code)->first();
    }

    /**
     * The compact catalogue shape injected into `PromptV2`.
     *
     * Deliberately narrow: code, name, need category and sample services are
     * everything the model needs to make a defensible referral. `contact_info`
     * is administrative and is NOT sent — the prompt should receive only what
     * it must cite, so the transmitted payload stays auditable against D3.
     *
     * @return array<int, array<string, string>>
     */
    public static function promptCatalogue(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn (self $a) => [
                'agency_code' => $a->agency_code,
                'agency_name' => $a->agency_name,
                'need_category' => $a->need_category,
                'sample_service' => (string) $a->sample_service,
            ])
            ->all();
    }

    /** The codes the catalogue currently permits — the validator's allow-list. */
    public static function activeCodes(): array
    {
        return static::active()->pluck('agency_code')->all();
    }

    /* ------------------------------------------------------------------ */
    /* Display */
    /* ------------------------------------------------------------------ */

    /**
     * "DSWD — Department of Social Welfare and Development".
     *
     * Used in the referral list so the Director sees the code the AI cited and
     * the agency it resolved to side by side.
     */
    public function getLabelAttribute(): string
    {
        return $this->agency_code.' — '.$this->agency_name;
    }

    /** The sample services as an array, for chips. */
    public function getSampleServicesAttribute(): array
    {
        if (blank($this->sample_service)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->sample_service))));
    }
}
