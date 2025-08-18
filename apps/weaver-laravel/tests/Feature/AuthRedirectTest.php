<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_dashboard_redirects_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_first_login_without_project_redirects_to_project_setup(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $this->post('/auth/self/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect();
        $this->get('/dashboard')->assertRedirect('/project/setup');
    }
}
