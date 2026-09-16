<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    public function test_allowed_role_passes_through(): void
    {
        $user = User::factory()->make(['role' => 'admin']);
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureRole)->handle($request, fn () => response('ok'), 'admin');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_disallowed_role_is_forbidden(): void
    {
        $user = User::factory()->make(['role' => 'secretary']);
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        try {
            (new EnsureRole)->handle($request, fn () => response('ok'), 'admin');
            $this->fail('Expected HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_guest_is_forbidden(): void
    {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        try {
            (new EnsureRole)->handle($request, fn () => response('ok'), 'admin');
            $this->fail('Expected HttpException.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
