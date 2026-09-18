<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_http_cannot_run_artisan_maintenance(): void
    {
        Artisan::shouldReceive('call')->never();
        foreach (['cache-clear', 'config-clear', 'route-clear', 'optimize-clear', 'storage-link'] as $path) {
            $this->getJson('/api/'.$path)->assertNotFound();
        }
    }

    public function test_logged_in_users_cannot_create_and_self_grant_permissions(): void
    {
        $user = User::factory()->create(['status' => true, 'email_verified_at' => now(), 'password' => 'password']);
        $this->actingAs($user, 'sanctum')->postJson('/api/add-permission', [
            'group_name' => 'staff', 'permissions' => ['arbitrary_admin_permission'],
        ])->assertStatus(405);
        $this->assertDatabaseMissing('permissions', ['name' => 'arbitrary_admin_permission']);
    }

    public function test_deploy_fails_closed_when_secret_is_missing_or_wrong(): void
    {
        $this->app->instance('env', 'production');
        Artisan::shouldReceive('call')->never();
        foreach ([null, '', 'configured-secret'] as $secret) {
            config(['app.deploy_key' => $secret]);
            $this->postJson('/__deploy/run')->assertForbidden();
        }
        $this->withHeader('X-Deploy-Key', 'wrong-secret')->postJson('/__deploy/run')->assertForbidden();
    }

    public function test_deploy_uses_migrations_without_seeding_or_rotating_the_app_key(): void
    {
        $this->app->instance('env', 'production');
        config(['app.deploy_key' => 'configured-secret', 'app.key' => str_repeat('a', 32)]);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
        foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
            Artisan::shouldReceive('call')->once()->with($command, [])->andReturn(0);
        }
        File::shouldReceive('put')->once()->andReturn(1);
        $this->withHeader('X-Deploy-Key', 'configured-secret')->postJson('/__deploy/run')
            ->assertOk()->assertJsonPath('status', 'DEPLOY_SUCCESS');
    }

    public function test_deploy_stops_and_returns_failure_when_migration_fails(): void
    {
        $this->app->instance('env', 'production');
        config(['app.deploy_key' => 'configured-secret', 'app.key' => str_repeat('a', 32)]);
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(1);
        File::shouldReceive('put')->never();
        $this->withHeader('X-Deploy-Key', 'configured-secret')->postJson('/__deploy/run')
            ->assertStatus(500)->assertJsonPath('status', 'DEPLOY_FAILED');
    }
}
