<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_creation_flow(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $this->post('/auth/self/login', ['email' => $user->email, 'password' => 'secret']);
        $payload = [
            'name' => 'My Project',
            'owner' => 'owner-name',
            'repo' => 'repo-name',
            'github_app_id' => '12345',
            'github_app_client_id' => 'lv1_client_abc'
        ];
        $this->post('/project/setup', $payload)
            ->assertRedirect('/dashboard');
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'owner' => 'owner-name',
            'repo' => 'repo-name'
        ]);
        $this->get('/project/setup')->assertRedirect('/dashboard');
    }

    public function test_invalid_owner_rejected(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $this->post('/auth/self/login', ['email' => $user->email, 'password' => 'secret']);
        $bad = [
            'name' => 'Bad',
            'owner' => 'INVALID OWNER!',
            'repo' => 'repo-name',
            'github_app_id' => '12345',
            'github_app_client_id' => 'lv1_client_abc'
        ];
        $response = $this->post('/project/setup', $bad);
        $response->assertSessionHasErrors(['owner']);
        $this->assertDatabaseCount('projects', 0);
    }
}
