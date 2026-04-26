<?php

namespace Tests\Feature\Staffs;

use App\Models\User;
use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_index_returns_grouped_permissions_and_assigned_role_permissions(): void
    {
        $viewer = $this->createUserWithPermissions(['role_permission_view']);
        $role = Role::create([
            'name' => 'role_permissions_index_' . uniqid(),
            'guard_name' => 'web',
        ]);

        $fieldPermission = $this->createPermission('field_list_view', 'field');
        $rolePermissionUpdate = $this->createPermission('role_permission_update', 'staff_role');
        $role->givePermissionTo($fieldPermission);

        Sanctum::actingAs($viewer);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/roles/{$role->id}/permissions");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertContains($fieldPermission->name, $response->json('data.rolePermissions'));
        $this->assertNotContains($rolePermissionUpdate->name, $response->json('data.rolePermissions'));

        $allPermissions = collect($response->json('data.allPermissions'))->keyBy('name');
        $allGroups = collect($response->json('data.allGroups'))->keyBy('group_name');
        $allParentGroups = collect($response->json('data.allParentGroups'))
            ->pluck('parent_group_name')
            ->values();

        $this->assertSame('field', $allPermissions['field_list_view']['group_name']);
        $this->assertSame('fields', $allPermissions['field_list_view']['parent_group_name']);
        $this->assertSame('fields', $allGroups['field']['parent_group_name']);
        $this->assertTrue($allParentGroups->contains('fields'));
    }

    public function test_role_permissions_index_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([]);
        $role = Role::create([
            'name' => 'role_permissions_index_denied_' . uniqid(),
            'guard_name' => 'web',
        ]);

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/roles/{$role->id}/permissions")
            ->assertForbidden();
    }

    public function test_role_permissions_update_syncs_permissions(): void
    {
        $updater = $this->createUserWithPermissions(['role_permission_update']);
        $role = Role::create([
            'name' => 'role_permissions_update_' . uniqid(),
            'guard_name' => 'web',
        ]);

        $oldPermission = $this->createPermission('role_permission_old_case', 'staff_role');
        $newPermission = $this->createPermission('role_permission_new_case', 'staff_role');
        $fieldPermission = $this->createPermission('field_data_update', 'field');

        $role->givePermissionTo($oldPermission);

        Sanctum::actingAs($updater);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->putJson("/api/roles/{$role->id}/permissions", [
                'permissions' => [$newPermission->name, $fieldPermission->name],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $freshRole = $role->fresh();
        $this->assertFalse($freshRole->hasPermissionTo($oldPermission->name));
        $this->assertTrue($freshRole->hasPermissionTo($newPermission->name));
        $this->assertTrue($freshRole->hasPermissionTo($fieldPermission->name));
    }

    public function test_role_permissions_update_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([]);
        $role = Role::create([
            'name' => 'role_permissions_update_denied_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $permission = $this->createPermission('role_permission_denied_case', 'staff_role');

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->putJson("/api/roles/{$role->id}/permissions", [
                'permissions' => [$permission->name],
            ])
            ->assertForbidden();
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = $this->createPermission($permissionName, 'staff_role');
            $user->givePermissionTo($permission);
        }

        return $user;
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
