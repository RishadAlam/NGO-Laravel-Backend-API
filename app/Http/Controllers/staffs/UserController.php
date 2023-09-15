<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\ChangeStatusRequest;
use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:staff_list_view')->only('index');
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
        $users = User::with('roles:id,name')
            ->get(['id', 'name', 'email', 'phone', 'image', 'image_uri', 'email_verified_at as verified_at', 'status']);
        $responseData = [];
        foreach ($users as $key => $user) {
            $responseData[$key] = (object) [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'image'         => $user->image,
                'image_uri'     => $user->image_uri,
                'verified_at'   => $user->verified_at,
                'status'        => $user->status,
                'role_id'       => $user->roles[0]->id ?? null,
                'role_name'     => $user->roles[0]->name ?? null,
            ];
        }

        return response(
            [
                'success'   => true,
                'data'      => $responseData
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StaffStoreRequest $request)
    {
        $staffData = (object) $request->validated();
        $staff = User::create([
            'name' => $staffData->name,
            'email' => $staffData->email,
            'phone' => $request->phone,
            'password' => Hash::make(123)
        ]);
        $staff->assignRole($staffData->role);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.successful'),
                'id'        => $staff->id
            ],
            200
        );
    }

    /**
     * Change Status the specified user
     */
    public function change_status(ChangeStatusRequest $request, string $id)
    {
        $id = User::find($id)->update(['status' => $request->validated()['status']]);
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.status')
            ],
            200
        );
    }

    /**
     * Display a list of user permissions
     */
    public function get_user_permissions(string $id)
    {
        $collectionOfPermissions = User::find($id)->permissions;
        $permissions             = [];
        foreach ($collectionOfPermissions as $permission) {
            $permissions[] = (object) [
                'id' => $permission->id,
                'name' => $permission->name,
                'group_name' => $permission->group_name
            ];
        }
        return response(
            [
                'success'   => true,
                'data'      => $permissions
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StaffUpdateRequest $request, string $id)
    {
        $staffData = (object) $request->validated();
        $staff = User::with('roles:id')->find($id);
        $staff->update([
            'name' => $staffData->name,
            'email' => $staffData->email,
            'phone' => $request->phone
        ]);
        if ($staffData->role !== $staff->roles[0]->id) {
            $staff->syncRoles($staff->roles[0]->id, $staffData->role);
        }

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.successful'),
                'id'        => $staff->id
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        User::find($id)->delete();
        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.delete')
            ],
            200
        );
    }
}
