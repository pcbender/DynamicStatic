<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_edit(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $this->post('/auth/self/login', ['email' => $user->email, 'password' => 'secret']);
        $this->post('/project/setup', [
            'name' => 'Proj', 'owner' => 'o', 'repo' => 'r', 'github_app_id' => '1', 'github_app_client_id' => 'cid'
        ]);
        $this->get('/project/edit')->assertStatus(200);
    }

    public function test_other_user_cannot_access_edit(): void
    {
        $a = User::factory()->create(['password' => bcrypt('secret')]);
        $b = User::factory()->create(['password' => bcrypt('secret')]);
        // User A creates project
        $this->post('/auth/self/login', ['email' => $a->email, 'password' => 'secret']);
        $this->post('/project/setup', [
            'name' => 'Proj', 'owner' => 'o', 'repo' => 'r', 'github_app_id' => '1', 'github_app_client_id' => 'cid'
        ]);
        // switch to B
        $this->post('/auth/self/login', ['email' => $b->email, 'password' => 'secret']);
        // B should not reach edit for A (will redirect to setup since B has no project)
        $this->get('/project/edit')->assertRedirect('/project/setup');
    }
}
