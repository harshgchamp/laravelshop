<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; 
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Resources\Admin\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{   
    public function index(UserIndexRequest $request)
    {
        $query = User::query();

        $users = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users), 
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $user->assignRole($request->role);
            DB::commit();
            return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully');
            // return back()->with('success', $user->name. ' created successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error creating user: ' . $th->getMessage());
        }
    }

    public function edit(User $user)
    {
        $roles = Role::all(); 
        return Inertia::render('Admin/Users/Edit', [
            'user' => (new UserResource($user))->resolve(),
            'roles' => $roles,
        ]);
    }


    public function update(UserUpdateRequest $request, User $user)
    { 
        DB::beginTransaction();
        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);
            $user->syncRoles([$request->role]);
            DB::commit();
            return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully');
            // return back()->with('success', $user->name. ' updated successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error updating ' . $user->name . $th->getMessage());
        }
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
    }
}
