<?php

namespace App\Http\Controllers\staffs;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
        $allPermissions = Permission::orderBy('name')
            ->get(['id', 'name', 'group_name']);
        $allGroups = Permission::select('group_name')
            ->distinct()
            ->orderBy('group_name')
            ->get();
        $userPermissions = User::find($id)
            ->getPermissionNames();

        return create_response(
            null,
            [
                'allGroups'         => $allGroups,
                'allPermissions'    => $allPermissions,
                'userPermissions'   => $userPermissions
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
