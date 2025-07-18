<?php

namespace App\Http\Controllers\staffs;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\StoreRoleRequest;
use App\Http\Requests\staffs\UpdateRoleRequest;

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
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $roleData   = (object) $request->validated();
        $role       = Role::create(['name' => $roleData->name, 'guard_name' => 'web']);
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.role.successful'),
                'id'        => $role->id,
                'name'      => $role->name,
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Role::find($id)->delete();
        return create_response(__('customValidations.role.delete'));
    }
}
