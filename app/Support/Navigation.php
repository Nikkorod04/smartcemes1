<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Nav resolution, shared by the sidebar and the topbar.
 *
 * WHY THIS EXISTS
 * ---------------
 * Both partials used to re-implement the same "is this item active?" heuristic
 * inline. Once the hierarchy is collapsed into ONE hub entry (prototype
 * PATTERNS v4.3) the heuristic also has to understand `subs[]` — and two copies
 * of that rule is exactly how the sidebar highlight and the page title drift
 * apart.
 */
class Navigation
{
    /**
     * The nav groups for a role, with items whose route does not exist removed.
     *
     * The sidebar silently hides a missing route, which is how the Faculty
     * Management entry stayed invisible for months — so the filtering happens
     * here, once, and `RouteSurfaceTest` asserts none are missing.
     *
     * @return array<int, array{section: string, items: array<int, array<string, mixed>>}>
     */
    public static function groups(string $role): array
    {
        return collect(config("smartcemes.nav.{$role}", []))
            ->map(fn (array $group) => [
                'section' => $group['section'],
                'items' => collect($group['items'] ?? [])
                    ->filter(fn (array $item) => Route::has($item['route']))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group) => count($group['items']) > 0)
            ->values()
            ->all();
    }

    /**
     * How many nav items belong to each route family (the segment before the
     * first dot). A family with exactly ONE item means that item owns every
     * route in the family — that is what keeps "Faculty Management" lit on the
     * directory and on a faculty profile.
     *
     * @param  array<int, array{section: string, items: array<int, array<string, mixed>>}>  $groups
     * @return Collection<string, int>
     */
    public static function families(array $groups): Collection
    {
        return collect($groups)->pluck('items')->flatten(1)
            ->map(fn (array $item) => str($item['route'])->before('.')->toString())
            ->countBy();
    }

    /**
     * Is this nav item the one the current request belongs to?
     *
     * Three ways to match:
     *  1. the exact route name;
     *  2. the item is the ONLY entry in its route family, so the whole family
     *     belongs to it;
     *  3. the request matches one of the item's declared `subs[]` patterns.
     *
     * (3) is what keeps the collapsed "Manage Extension Programs" hub lit on
     * `/programs`, `/projects` and a project hub — none of which are sidebar
     * items any more.
     *
     * @param  array<string, mixed>  $item
     * @param  Collection<string, int>  $families
     */
    public static function isActive(array $item, Collection $families): bool
    {
        if (request()->routeIs($item['route'])) {
            return true;
        }

        if (url()->current() === route($item['route'])) {
            return true;
        }

        $family = str($item['route'])->before('.')->toString();

        if ($families->get($family) === 1 && request()->routeIs($family.'.*')) {
            return true;
        }

        foreach ($item['subs'] ?? [] as $sub) {
            if (request()->routeIs($sub)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The label the topbar shows. A sub-page of a collapsed hub resolves to the
     * HUB's label, so a project hub still reads "Manage Extension Programs".
     */
    public static function pageLabel(string $role): string
    {
        $groups = static::groups($role);
        $items = collect($groups)->pluck('items')->flatten(1)->values();
        $families = static::families($groups);

        foreach ($items as $item) {
            if (static::isActive($item, $families)) {
                return $item['label'];
            }
        }

        return 'SmartCEMES';
    }
}
