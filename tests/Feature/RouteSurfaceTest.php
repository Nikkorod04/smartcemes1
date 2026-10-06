<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every application surface must actually render.
 *
 * WHY THIS EXISTS (R7 hardening)
 * ------------------------------
 * `GET /my-projects` returned **HTTP 500** — `View [livewire.projects.my] not
 * found.` The component asked for `livewire.projects.my` while the file lives at
 * `livewire/programs/my.blade.php`, a leftover from the R2 rename. Three sibling
 * components in the same directory all used the correct `livewire.programs.*`
 * prefix; this one did not.
 *
 * It survived a 437-test suite because **nothing tested that page at all** —
 * `grep -rn "projects.my" tests/` returned zero hits. Every existing test went
 * through `Livewire::test(SomeComponent::class)`, which constructs the component
 * directly and never resolves a route, a middleware stack, or the nav. So a
 * broken *route→view* path was structurally invisible.
 *
 * These tests walk the surfaces the way a user reaches them — by URL — so the
 * whole class of "the page is broken" bugs fails here instead of in a demo.
 */
class RouteSurfaceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    /**
     * Every nav item — and every `subs[]` entry it declares — must point at a
     * route that exists.
     *
     * The sidebar silently HIDES items whose route is missing, which is how the
     * Faculty Management entry stayed invisible for months. A nav entry that
     * resolves to nothing is therefore a bug with no visible symptom — so it is
     * asserted here rather than left to be noticed.
     *
     * `subs[]` matters just as much: it is what keeps the collapsed hub entry
     * highlighted, and a typo there fails silently (the highlight simply stops).
     */
    public function test_every_nav_item_points_at_a_real_route(): void
    {
        $registered = collect(app('router')->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->all();

        $missing = [];

        foreach (config('smartcemes.nav') as $role => $sections) {
            foreach ($sections as $section) {
                foreach ($section['items'] ?? [] as $item) {
                    foreach (array_merge([$item['route'] ?? null], $item['subs'] ?? []) as $name) {
                        if ($name === null || ! in_array($name, $registered, true)) {
                            $missing[] = "{$role}/".($section['section'] ?? '?').'/'.($item['key'] ?? '?').' → '.($name ?? 'none');
                        }
                    }
                }
            }
        }

        $this->assertSame([], $missing, "Nav entries pointing at routes that do not exist:\n".implode("\n", $missing));
    }

    /**
     * The regression that started this file. Pinned by name so it cannot come
     * back silently.
     */
    public function test_the_my_projects_page_renders(): void
    {
        $this->seed();

        $facultyUser = Faculty::with('user')->firstOrFail()->user;

        $this->actingAs($facultyUser)
            ->get(route('projects.my'))
            ->assertOk()
            ->assertSee('My Projects');
    }

    /**
     * Walk every admin nav surface. A 5xx here is always a bug — the Director
     * reaching a broken page is the worst case, because it is the demo path.
     */
    public function test_every_admin_nav_surface_renders_without_a_server_error(): void
    {
        $this->assertNoServerErrors($this->admin(), $this->navRoutesFor('admin'));
    }

    public function test_every_secretary_nav_surface_renders_without_a_server_error(): void
    {
        $this->seed();

        $secretary = User::where('email', 'secretary@lnu.com')->firstOrFail();

        $this->assertNoServerErrors($secretary, $this->navRoutesFor('secretary'));
    }

    public function test_every_faculty_nav_surface_renders_without_a_server_error(): void
    {
        $this->seed();

        $facultyUser = Faculty::with('user')->firstOrFail()->user;

        $this->assertNoServerErrors($facultyUser, $this->navRoutesFor('faculty'));
    }

    /**
     * The surfaces a user reaches that are NOT in the nav — detail pages, the
     * print reports, and the import templates. These are exactly the routes that
     * drift unnoticed, because nothing links them from the sidebar.
     */
    public function test_the_unlinked_surfaces_render_without_a_server_error(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@lnu.com')->firstOrFail();
        $project = ExtensionProject::firstOrFail();
        $faculty = Faculty::firstOrFail();

        $uris = [
            route('projects.show', ['project' => $project]),
            route('faculty.show', ['faculty' => $faculty]),
            route('faculty.directory'),
            route('reports.annual'),
            route('reports.community-impact'),
            route('reports.rendered-hours'),
            route('reports.results-framework', ['program' => $project]),
        ];

        foreach ($uris as $uri) {
            $status = $this->actingAs($admin)->get($uri)->getStatusCode();

            $this->assertLessThan(
                500,
                $status,
                "GET {$uri} returned {$status}. An unlinked surface is still a surface a user can reach."
            );
        }
    }

    /**
     * The import templates are downloads rather than pages, but they still have
     * to build — a broken one fails only when someone is standing at the front
     * of a training session trying to take attendance.
     */
    public function test_the_import_templates_download(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@lnu.com')->firstOrFail();
        $activity = Activity::firstOrFail();

        foreach ([
            route('activities.attendance-template', ['activity' => $activity]),
            route('activities.evaluation-template', ['activity' => $activity]),
            route('beneficiaries.template'),
            route('assessments.template'),
        ] as $uri) {
            $status = $this->actingAs($admin)->get($uri)->getStatusCode();

            $this->assertLessThan(500, $status, "GET {$uri} returned {$status}.");
        }
    }

    /**
     * Every route a role can reach from its nav — the items themselves PLUS
     * each item's declared `subs[]`.
     *
     * WHY SUBS MATTER HERE (R7)
     * -------------------------
     * The hierarchy is collapsed into ONE hub entry (prototype PATTERNS v4.3),
     * so `/programs` and `/projects` are no longer nav ITEMS — they are subs of
     * the hub. Walking items only would silently drop their 5xx coverage, which
     * is exactly the class of bug this file exists to catch. Parameterised
     * routes are skipped (they need a model instance; the unlinked-surfaces test
     * covers `projects.show`).
     *
     * @return array<int, string>
     */
    private function navRoutesFor(string $role): array
    {
        $routes = [];

        foreach (config("smartcemes.nav.{$role}", []) as $section) {
            foreach ($section['items'] ?? [] as $item) {
                foreach (array_merge([$item['route'] ?? null], $item['subs'] ?? []) as $name) {
                    if ($name === null || ! app('router')->has($name)) {
                        continue;
                    }

                    // A route needing a parameter cannot be walked by name alone.
                    if (app('router')->getRoutes()->getByName($name)->parameterNames() !== []) {
                        continue;
                    }

                    $routes[] = $name;
                }
            }
        }

        return array_values(array_unique($routes));
    }

    /**
     * @param  array<int, string>  $routeNames
     */
    private function assertNoServerErrors(User $user, array $routeNames): void
    {
        $this->assertNotEmpty($routeNames, 'Expected the nav to declare at least one route for this role.');

        $failures = [];

        foreach ($routeNames as $name) {
            if (! app('router')->has($name)) {
                $failures[] = "{$name} → route does not exist";

                continue;
            }

            $uri = route($name);
            $response = $this->actingAs($user)->get($uri);
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $failures[] = "{$name} ({$uri}) → HTTP {$status}";
            }
        }

        $this->assertSame([], $failures, "Surfaces returned a server error:\n".implode("\n", $failures));
    }
}
