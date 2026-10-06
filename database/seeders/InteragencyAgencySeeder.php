<?php

namespace Database\Seeders;

use App\Models\InteragencyAgency;
use Illuminate\Database\Seeder;

/**
 * Phase R6 / D-R10 — the starter interagency catalogue (§7.2).
 *
 * WHERE THIS LIST COMES FROM
 * --------------------------
 * Not invented. CESO's own published page lists a second category of extension
 * work alongside its six training thrusts — *Community Outreach Programs*,
 * covering Food/Nutrition/Health, Medical/Dental/Optical Missions, and Clean &
 * Green/Coastal Clean-up (§3). Those categories are reclassified here as
 * **referral** targets rather than CESO programmes, for two reasons the owner
 * agreed:
 *
 *   1. they are one-off service delivery, so they do not fit the
 *      `trainors x trainees x days` measurement model that drives performance;
 *   2. they are precisely what the adviser named as out-of-scope
 *      recommendations (feeding programmes, medical missions, clean-ups).
 *
 * So the system stays faithful to CESO's published priorities while keeping the
 * recommendation surface inside CESO's training mandate.
 *
 * IDEMPOTENT BY `agency_code`
 * ---------------------------
 * `updateOrCreate` on the code, so re-seeding refreshes the wording without
 * duplicating rows and without disturbing an agency the Director has since
 * edited the *name* of. `sort_order` is assigned here so the catalogue reads in
 * the same sequence as §7.2.
 *
 * The owner reviews and edits these rows in the UI; the AI can cite only what is
 * in this table, which is what makes every referral defensible.
 */
class InteragencyAgencySeeder extends Seeder
{
    public function run(): void
    {
        $agencies = [
            [
                'agency_code' => 'DSWD',
                'agency_name' => 'Department of Social Welfare and Development',
                'mandate' => 'Social welfare and development',
                'need_category' => 'Food / nutrition / welfare',
                'sample_service' => 'Supplementary feeding, 4Ps, senior social pension',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'DOH',
                'agency_name' => 'Department of Health',
                'mandate' => 'National health services',
                'need_category' => 'Health / medical',
                'sample_service' => 'Medical and dental missions, immunization',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'DA',
                'agency_name' => 'Department of Agriculture',
                'mandate' => 'Agriculture and fisheries',
                'need_category' => 'Farming / fishing livelihood inputs',
                'sample_service' => 'Techno-demo, farm inputs, training support',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'DENR',
                'agency_name' => 'Department of Environment and Natural Resources',
                'mandate' => 'Natural resources and environment',
                'need_category' => 'Environmental rehabilitation',
                'sample_service' => 'Coastal and watershed rehabilitation, tree growing',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'TESDA',
                'agency_name' => 'Technical Education and Skills Development Authority',
                'mandate' => 'Technical-vocational skills',
                'need_category' => 'Skills certification',
                'sample_service' => 'Free skills assessment and certification',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'DPWH',
                'agency_name' => 'Department of Public Works and Highways',
                'mandate' => 'Public works and infrastructure',
                'need_category' => 'Roads / drainage / facilities',
                'sample_service' => 'Barangay road and drainage construction',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'DTI',
                'agency_name' => 'Department of Trade and Industry',
                'mandate' => 'Trade and industry',
                'need_category' => 'Enterprise development',
                'sample_service' => 'MSME mentoring, product development',
                'contact_info' => null,
            ],
            [
                'agency_code' => 'LGU',
                'agency_name' => 'Local Government Unit (Barangay / City)',
                'mandate' => 'Local governance and basic services',
                'need_category' => 'Water, sanitation, local facilities',
                'sample_service' => 'Water system maintenance, sanitation enforcement',
                'contact_info' => null,
            ],
        ];

        foreach ($agencies as $i => $agency) {
            InteragencyAgency::withTrashed()->updateOrCreate(
                ['agency_code' => $agency['agency_code']],
                $agency + [
                    'sort_order' => ($i + 1) * 10,
                    'active' => true,
                    // Re-seeding restores a retired agency, which is the
                    // expected behaviour for a "starter set" seed.
                    'deleted_at' => null,
                ]
            );
        }
    }
}
