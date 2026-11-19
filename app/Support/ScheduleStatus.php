<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Status/date consistency for projects and activities.
 *
 * A plan that has not started yet cannot be ongoing, completed, or cancelled:
 * before the planned start it exists as a draft. From the start date onward the
 * other statuses are legitimate. Existing records that already carry one of the
 * blocked statuses are allowed to keep it on edit (the caller decides whether
 * the status or start actually changed), so seeded/legacy rows stay editable.
 */
class ScheduleStatus
{
    public const BLOCKED_BEFORE_START = ['ongoing', 'completed', 'cancelled'];

    public static function error(?string $status, ?string $plannedStart, string $subject): ?string
    {
        if ($status === null || ! in_array($status, self::BLOCKED_BEFORE_START, true)) {
            return null;
        }

        if ($plannedStart === null || $plannedStart === '') {
            return null;
        }

        if (! Carbon::parse($plannedStart)->startOfDay()->isFuture()) {
            return null;
        }

        return "A {$subject} can only be marked Draft before its planned start date. Keep it as Draft until then.";
    }

    /**
     * The statuses a select may offer. Before the start only Draft is valid;
     * a legacy current selection is kept visible so the edit form can show it.
     *
     * @param  array<int, string>  $statuses
     * @return array<int, string>
     */
    public static function selectable(?string $plannedStart, ?string $current, array $statuses): array
    {
        if ($plannedStart === null || $plannedStart === '' || ! Carbon::parse($plannedStart)->startOfDay()->isFuture()) {
            return $statuses;
        }

        $allowed = ['draft'];

        if ($current !== null && in_array($current, $statuses, true) && ! in_array($current, $allowed, true)) {
            $allowed[] = $current;
        }

        return $allowed;
    }
}
