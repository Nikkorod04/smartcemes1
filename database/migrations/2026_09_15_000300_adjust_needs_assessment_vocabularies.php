<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Blueprint v4.9 vocabulary adjustments on needs_assessments:
 * - drops interested_in_livelihood_training (removed from the instrument)
 * - flattens the sixteen former multi-select columns (stored as JSON arrays)
 *   into plain string columns
 * - remaps values dropped from the vocabularies (civil status, religion,
 *   water source, closed multi lists) so existing rows stay valid.
 */
return new class extends Migration
{
    /** Former array columns that are now single-select strings. */
    protected const SINGLE_FIEDS = [
        'respondent_educational_attainment',
        'family_composition',
        'household_members_in_organization',
        'livelihood_options',
        'desired_training',
        'areas_of_educational_interest',
        'common_illnesses',
        'action_when_sick',
        'water_source',
        'garbage_disposal_method',
        'toilet_type',
        'house_type',
        'tenure_status',
        'light_source_without_power',
        'organization_types',
        'organization_usual_activities',
    ];

    /** Multi columns whose vocabularies lost options: field => removed options. */
    protected const MULTI_FILTERS = [
        'appliances_owned' => ['None', 'Other'],
        'barangay_recreational_facilities' => ['None', 'Other'],
        'use_of_free_time' => ['Other'],
        'family_problems' => ['Other'],
        'health_problems' => ['Other'],
        'educational_problems' => ['Other'],
        'employment_problems' => ['Other'],
        'infrastructure_problems' => ['Other'],
        'economic_problems' => ['Other'],
        'security_problems' => ['Other'],
    ];

    /** Single columns whose vocabularies lost options: values => null. */
    protected const SINGLE_REMAPS = [
        'respondent_civil_status' => ['Live-in' => null, 'Unknown' => null],
        'respondent_religion' => [
            'Protestant' => 'Evangelical Christianity',
            'Born Again / Christian' => 'Evangelical Christianity',
            'No religion / Prefer not to say' => 'Prefer not to say',
            'Other' => null,
        ],
        'water_source' => ['Bottled water' => 'Other'],
        'position_in_organization' => ['Other' => null],
        'reason_not_available' => ['Other' => null],
    ];

    public function up(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->dropColumn('interested_in_livelihood_training');
        });

        // The sixteen former multi-select columns were json NOT NULL with a
        // '[]' default — single-select values are nullable strings now.
        Schema::table('needs_assessments', function (Blueprint $table) {
            foreach (self::SINGLE_FIEDS as $field) {
                $table->string($field)->nullable()->change();
            }
        });

        if (! DB::table('needs_assessments')->exists()) {
            return;
        }

        foreach (DB::table('needs_assessments')->get() as $row) {
            $updates = [];

            foreach (self::SINGLE_FIEDS as $field) {
                $value = $row->{$field} ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $decoded = json_decode((string) $value, true);
                $updates[$field] = is_array($decoded)
                    ? ($decoded[0] ?? null)
                    : $value;
            }

            foreach (self::SINGLE_REMAPS as $field => $map) {
                $value = $updates[$field] ?? $row->{$field} ?? null;

                if ($value !== null && array_key_exists((string) $value, $map)) {
                    $updates[$field] = $map[(string) $value];
                }
            }

            foreach (self::MULTI_FILTERS as $field => $removed) {
                $value = $row->{$field} ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $decoded = json_decode((string) $value, true);

                if (is_array($decoded)) {
                    $filtered = array_values(array_filter(
                        $decoded,
                        fn ($item) => is_string($item) && ! in_array($item, $removed, true)
                    ));
                    $updates[$field] = json_encode(
                        count($filtered) > 3 ? array_slice($filtered, 0, 3) : $filtered
                    );
                }
            }

            if ($updates !== []) {
                DB::table('needs_assessments')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->string('interested_in_livelihood_training')->nullable();
        });
    }
};
