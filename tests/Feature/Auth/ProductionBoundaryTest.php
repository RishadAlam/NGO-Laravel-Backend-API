<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
}
