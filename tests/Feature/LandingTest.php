<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_landing_page_without_accessing_the_workspace(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Landing', false)
                    ->where('auth.user', null)
                    ->where('business', []),
            );

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/pos')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_reopen_the_landing_with_their_session(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Landing', false)
                    ->where('auth.user.id', $owner->id)
                    ->where('auth.user.role', 'owner')
                    ->missing('auth.user.password'),
            );
    }
}
