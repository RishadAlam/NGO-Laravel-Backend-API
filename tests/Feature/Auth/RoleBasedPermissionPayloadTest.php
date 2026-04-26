<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleBasedPermissionPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorization_returns_effective_permissions_from_direct_and_role_assignments(): void
    {
        $user = $this->createVerifiedActiveUser('auth-role@example.com', 'password');
        $directPermission = $this->createPermission('auth_direct_permission', 'staff');
        $rolePermission = $this->createPermission('auth_role_permission', 'staff');

        $user->givePermissionTo($directPermission);

        $role = Role::create([
            'name' => 'auth_role_'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($rolePermission);
        $user->assignRole($role);

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/authorization');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $permissions = $response->json('permissions');
        $this->assertContains('auth_direct_permission', $permissions);
        $this->assertContains('auth_role_permission', $permissions);
    }

    public function test_login_returns_effective_permissions_from_direct_and_role_assignments(): void
    {
        $email = 'auth-login-role@example.com';
        $password = 'password';

        $user = $this->createVerifiedActiveUser($email, $password);
        $directPermission = $this->createPermission('login_direct_permission', 'staff');
        $rolePermission = $this->createPermission('login_role_permission', 'staff');

        $user->givePermissionTo($directPermission);

        $role = Role::create([
            'name' => 'login_role_'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($rolePermission);
        $user->assignRole($role);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/login', [
                'email' => $email,
                'password' => $password,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $permissions = $response->json('permissions');
        $this->assertContains('login_direct_permission', $permissions);
        $this->assertContains('login_role_permission', $permissions);
    }

    public function test_authorization_returns_role_permissions_when_relations_are_stale_on_authenticated_user(): void
    {
        $user = $this->createVerifiedActiveUser('stale-relations@example.com', 'password');
        $rolePermission = $this->createPermission('stale_role_permission', 'staff');
        $role = Role::create([
            'name' => 'stale_relations_role_'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($rolePermission);
        $user->assignRole($role);

        // Simulate stale in-memory auth model where relations were previously loaded as empty.
        $user->setRelation('roles', collect());
        $user->setRelation('permissions', collect());

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/authorization');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $roles = $response->json('role');
        $permissions = $response->json('permissions');

        $this->assertContains($role->name, $roles);
        $this->assertContains('stale_role_permission', $permissions);
    }

    private function createVerifiedActiveUser(string $email, string $password): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => bcrypt($password),
            'status' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createPermission(string $name, string $groupName): Permission
    {
        return Permission::updateOrCreate(
            ['name' => $name],
            [
                'group_name' => $groupName,
                'parent_group_name' => PermissionParentCategoryResolver::resolve($groupName),
                'guard_name' => 'web',
            ]
        );
    }
}
