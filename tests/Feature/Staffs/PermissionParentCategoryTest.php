<?php

namespace Tests\Feature\Staffs;

use App\Models\User;
use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionParentCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_index_includes_parent_categories_without_changing_child_groups(): void
    {
        $viewer = $this->createUserWithPermissions(['staff_permission_view']);
        $staff = $this->createUserWithPermissions([]);

        $fieldPermission = $this->createPermission('field_list_view', 'field');
        $pendingCollectionPermission = $this->createPermission(
            'pending_loan_collection_list_view',
            'pending_loan_collection'
        );
        $customPermission = $this->createPermission('custom_permission_for_tests', 'custom_module');

        $staff->givePermissionTo([
            $fieldPermission->name,
            $pendingCollectionPermission->name,
            $customPermission->name,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/permissions/{$staff->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $allPermissions = collect($response->json('data.allPermissions'))->keyBy('name');
        $allGroups = collect($response->json('data.allGroups'))->keyBy('group_name');
        $allParentGroups = collect($response->json('data.allParentGroups'))
            ->pluck('parent_group_name')
            ->values();

        $this->assertSame('field', $allPermissions['field_list_view']['group_name']);
        $this->assertSame('fields', $allPermissions['field_list_view']['parent_group_name']);
        $this->assertSame(
            'pending_collections',
            $allPermissions['pending_loan_collection_list_view']['parent_group_name']
        );
        $this->assertSame(
            PermissionParentCategoryResolver::DEFAULT_PARENT_CATEGORY,
            $allPermissions['custom_permission_for_tests']['parent_group_name']
        );

        $this->assertSame('fields', $allGroups['field']['parent_group_name']);
        $this->assertSame(
            'pending_collections',
            $allGroups['pending_loan_collection']['parent_group_name']
        );
        $this->assertTrue($allParentGroups->contains('fields'));
        $this->assertTrue($allParentGroups->contains('pending_collections'));

        $this->assertContains('field_list_view', $response->json('data.userPermissions'));
        $this->assertContains('pending_loan_collection_list_view', $response->json('data.userPermissions'));
        $this->assertContains('custom_permission_for_tests', $response->json('data.userPermissions'));
        $this->assertContains('field_list_view', $response->json('data.userDirectPermissions'));
        $this->assertContains('custom_permission_for_tests', $response->json('data.userDirectPermissions'));
        $this->assertSame([], $response->json('data.userRolePermissions'));
    }

    public function test_permissions_index_includes_role_based_permissions_in_effective_permissions(): void
    {
        $viewer = $this->createUserWithPermissions(['staff_permission_view']);
        $staff = $this->createUserWithPermissions([]);

        $rolePermission = $this->createPermission('role_based_permission_for_staff', 'staff');
        $role = Role::create([
            'name' => 'role_permission_test_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($rolePermission->name);
        $staff->assignRole($role);

        Sanctum::actingAs($viewer);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson("/api/permissions/{$staff->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertContains('role_based_permission_for_staff', $response->json('data.userRolePermissions'));
        $this->assertNotContains('role_based_permission_for_staff', $response->json('data.userDirectPermissions'));
        $this->assertContains('role_based_permission_for_staff', $response->json('data.userPermissions'));
    }

    public function test_users_index_includes_parent_group_name_on_permission_items(): void
    {
        $viewer = $this->createUserWithPermissions(['staff_list_view']);
        $staff = $this->createUserWithPermissions([]);

        $loanPermission = $this->createPermission('client_loan_account_update', 'client_loan_account');
        $staff->givePermissionTo($loanPermission->name);

        Sanctum::actingAs($viewer);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $staffRow = collect($response->json('data'))->firstWhere('id', $staff->id);
        $this->assertNotNull($staffRow);

        $permissionRow = collect($staffRow['permissions'])
            ->firstWhere('name', 'client_loan_account_update');

        $this->assertNotNull($permissionRow);
        $this->assertSame('client_loan_account', $permissionRow['group_name']);
        $this->assertSame('loan_accounts', $permissionRow['parent_group_name']);
        $this->assertTrue($permissionRow['is_direct']);
        $this->assertFalse($permissionRow['inherited']);

        $rolePermission = $this->createPermission('role_permission_for_user_list', 'staff');
        $role = Role::create([
            'name' => 'role_user_list_test_' . uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($rolePermission->name);
        $staff->assignRole($role);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $staffRow = collect($response->json('data'))->firstWhere('id', $staff->id);
        $rolePermissionRow = collect($staffRow['permissions'])
            ->firstWhere('name', 'role_permission_for_user_list');

        $this->assertNotNull($rolePermissionRow);
        $this->assertFalse($rolePermissionRow['is_direct']);
        $this->assertTrue($rolePermissionRow['inherited']);
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = $this->createPermission($permissionName, 'staff');
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
