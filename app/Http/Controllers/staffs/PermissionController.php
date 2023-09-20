<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
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
        $user_permissions = User::find($id)
            ->getPermissionNames();

        return response(
            [
                'success'   => true,
                'data'      => [
                    'allGroups'         => $allGroups,
                    'allPermissions'    => $allPermissions,
                    'user_permissions'  => $user_permissions
                ]
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}
