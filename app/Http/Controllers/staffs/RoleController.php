<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\StoreRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // $this->middleware('can:staff_list_view')->only('index');
        $this->middleware('can:staff_permissions_view')->only('show');
        $this->middleware('can:staff_registration')->only('store');
        $this->middleware('can:staff_data_update')->only('update');
        $this->middleware('can:staff_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all(['id', 'name', 'is_default']);

        return response(
            [
                'success'   => true,
                'data'      => $roles
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create(['name' => $request->name]);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.role.successfull'),
                'id'        => $role->id,
                'name'      => $role->name,
            ],
            200
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Role::find($id)->delete();

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.role.delete')
            ],
            200
        );
    }
}
