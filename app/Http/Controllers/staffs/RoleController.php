<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\StoreRoleRequest;
use App\Http\Requests\staffs\UpdateRoleRequest;
use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:role_list_view')->only('index');
        $this->middleware('can:role_registration')->only('store');
        $this->middleware('can:role_update')->only('update');
        $this->middleware('can:role_delete')->only('destroy');
        $this->middleware('can:role_permission_view')->only('permissions');
        $this->middleware('can:role_permission_update')->only('update_permissions');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all(['id', 'name', 'is_default']);

        return create_response(null, $roles);
    }

    /**
     * Display role permission data.
     */
    public function permissions(string $id)
    {
        $role = Role::findOrFail($id);

        $allPermissions = Permission::orderBy('name')
            ->get(['id', 'name', 'group_name', 'parent_group_name'])
            ->map(function ($permission) {
                return (object) [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'group_name' => $permission->group_name,
                    'parent_group_name' => PermissionParentCategoryResolver::resolve(
                        $permission->group_name,
                        $permission->parent_group_name
                    ),
                ];
            });

        $allGroups = $allPermissions
            ->map(function ($permission) {
                return (object) [
                    'group_name' => $permission->group_name,
                    'parent_group_name' => $permission->parent_group_name,
                ];
            })
            ->unique('group_name')
            ->sortBy('group_name')
            ->values();

        $allParentGroups = $allGroups
            ->pluck('parent_group_name')
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($parentGroupName) => (object) ['parent_group_name' => $parentGroupName]);

        $rolePermissions = $role
            ->permissions()
            ->pluck('name')
            ->values();

        return create_response(
            null,
            [
                'role' => (object) [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_default' => (bool) $role->is_default,
                    'permissions_count' => $rolePermissions->count(),
                ],
                'allGroups' => $allGroups,
                'allParentGroups' => $allParentGroups,
                'allPermissions' => $allPermissions,
                'rolePermissions' => $rolePermissions,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $roleData = (object) $request->validated();
        $role = Role::create(['name' => $roleData->name, 'guard_name' => 'web']);

        return response(
            [
                'success' => true,
                'message' => __('customValidations.role.successful'),
                'id' => $role->id,
                'name' => $role->name,
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = (object) $request->validated();
        Role::find($id)->update(['name' => $role->name]);

        return create_response(__('customValidations.role.update'));
    }

    /**
     * Update role permissions.
     */
    public function update_permissions(Request $request, string $id)
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        $validated = $request->validate(
            [
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['required', 'string', "exists:{$permissionsTable},name"],
            ]
        );

        $permissions = collect($validated['permissions'] ?? [])
            ->filter(fn ($permissionName) => is_string($permissionName) && trim($permissionName) !== '')
            ->unique()
            ->values()
            ->all();

        Role::findOrFail($id)->syncPermissions($permissions);

        return create_response(__('customValidations.role.permission_update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Role::find($id)->delete();

        return create_response(__('customValidations.role.delete'));
    }
}
