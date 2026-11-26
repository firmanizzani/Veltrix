<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;
use Tests\TestCase;

class LoginSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sets_the_seven_day_session_without_a_remember_cookie(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['password' => 'SessionPass12345']);
        $recallerName = Auth::guard()->getRecallerName();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'SessionPass12345',
            'remember' => true,
        ])
            ->assertRedirect()
            ->assertSessionHas('auth.login_at', now()->timestamp)
            ->assertCookieMissing($recallerName);

        $this->assertAuthenticatedAs($user);
        $this->assertSame(10080, config('session.lifetime'));
        $this->assertSame(10080, config('session.absolute_lifetime'));
        $this->assertFalse(config('session.expire_on_close'));
    }

    public function test_activity_preserves_the_original_login_time_and_expires_at_seven_days(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $loginAt = now()->timestamp;
        $this->actingAs($user)->withSession(['auth.login_at' => $loginAt]);

        $this->travel(3)->days();
        $this->get('/profile')->assertOk()->assertSessionHas('auth.login_at', $loginAt);

        $this->travel(4)->days();
        $this->get('/profile')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_expired_session_cannot_submit_changes(): void
    {
        $user = User::factory()->create(['name' => 'Original name']);
        $this->actingAs($user)
            ->withSession(['auth.login_at' => now()->subDays(7)->timestamp])
            ->put('/profile', ['name' => 'Changed name', 'email' => $user->email])
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame('Original name', $user->fresh()->name);
    }

    public function test_legacy_remember_cookies_cannot_restart_the_seven_day_limit(): void
    {
        $user = User::factory()->create();
        $guard = Auth::guard();
        $guard->login($user, true);
        $recallerName = $guard->getRecallerName();
        $recaller = collect(Cookie::getQueuedCookies())->first(
            fn(HttpCookie $cookie) => $cookie->getName() === $recallerName,
        );
        $this->assertNotNull($recaller);
        $this->app['session']->invalidate();
        Auth::forgetGuards();
        Cookie::flushQueuedCookies();

        $this->withCookie($recallerName, $recaller->getValue())
            ->get('/profile')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
