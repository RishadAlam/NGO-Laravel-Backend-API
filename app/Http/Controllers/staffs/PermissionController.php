<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:staff_permission_view')->only('index');
        $this->middleware('can:staff_permission_update')->only('update');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(string $id)
    {
        $user = User::with('roles:id,name')->findOrFail($id);

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

        $userDirectPermissions = $user
            ->getPermissionNames();
        $userRolePermissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->unique()
            ->values();
        $userRoleNames = $user->roles
            ->pluck('name')
            ->unique()
            ->values();
        $userPermissions = $userDirectPermissions
            ->merge($userRolePermissions)
            ->unique()
            ->values();

        return create_response(
            null,
            [
                'allGroups' => $allGroups,
                'allParentGroups' => $allParentGroups,
                'allPermissions' => $allPermissions,
                'user' => (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'image_uri' => $user->image_uri,
                    'status' => (bool) $user->status,
                    'roles' => $userRoleNames,
                ],
                'userDirectPermissions' => $userDirectPermissions,
                'userRolePermissions' => $userRolePermissions,
                'userPermissions' => $userPermissions,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        User::find($id)->syncPermissions($request->permissions);

        return create_response(__('customValidations.permission.update'));
    }
}
