<?php

namespace App\Services;

/**
 * The one set of beneficiary validation rules (2026-11-20).
 *
 * Names are letters (including ñ/Ñ), spaces, periods, hyphens and apostrophes.
 * Contact numbers allow digits, spaces, plus, hyphens and parentheses, and must
 * contain at least seven digits. Blank contact is allowed and means "not
 * provided" - it is never replaced with a fake number.
 */
class BeneficiaryRules
{
    public const NAME_REGEX = '/^[\p{L}\p{M}\s.\'-]+$/u';

    public const CONTACT_REGEX = '/^(?=(?:\D*\d){7,})[0-9+\s()\-]{7,20}$/';

    /** @return array<int, string> */
    public static function name(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:255',
            'regex:'.self::NAME_REGEX,
        ];
    }

    /** @return array<int, string> */
    public static function contact(): array
    {
        return ['nullable', 'string', 'max:20', 'regex:'.self::CONTACT_REGEX];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $ageRequired = true, string $prefix = '', bool $projectRequired = true): array
    {
        $p = $prefix === '' ? '' : $prefix.'.';

        return [
            $p.'first_name' => self::name(),
            $p.'middle_name' => self::name(false),
            $p.'last_name' => self::name(),
            $p.'age' => $ageRequired
                ? ['required', 'integer', 'min:1', 'max:120']
                : ['nullable', 'integer', 'min:1', 'max:120'],
            $p.'gender' => ['nullable', 'string', 'in:'.implode(',', config('smartcemes.beneficiary_genders'))],
            $p.'phone' => self::contact(),
            $p.'community_id' => ['required', 'integer', 'exists:communities,id'],
            $p.'beneficiary_category' => ['required', 'in:'.implode(',', config('smartcemes.beneficiary_categories'))],
            $p.'extension_project_id' => $projectRequired
                ? ['required', 'integer', 'exists:extension_projects,id']
                : ['nullable', 'integer', 'exists:extension_projects,id'],
        ];
    }

    /**
     * Human messages for the fields whose regex otherwise says only "format is
     * invalid". The caller merges these into validate().
     *
     * @return array<string, string>
     */
    public static function messages(string $prefix = ''): array
    {
        $p = $prefix === '' ? '' : $prefix.'.';

        return [
            $p.'first_name.regex' => 'First name may contain letters, spaces, periods, hyphens and apostrophes only.',
            $p.'middle_name.regex' => 'Middle name may contain letters, spaces, periods, hyphens and apostrophes only.',
            $p.'last_name.regex' => 'Last name may contain letters, spaces, periods, hyphens and apostrophes only.',
            $p.'phone.regex' => 'Contact number may contain digits, spaces, +, hyphens and parentheses, with at least 7 digits.',
            $p.'community_id.required' => 'Choose the community or partner school this beneficiary belongs to.',
            $p.'extension_project_id.required' => 'Choose the project this beneficiary belongs to.',
        ];
    }

    /** Normalise a typed contact: blank stays blank, otherwise trim. */
    public static function normaliseContact(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
