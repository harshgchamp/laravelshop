<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Requests\Admin\PermissionIndexRequest;
use App\Http\Requests\Admin\PermissionStoreRequest;
use App\Http\Requests\Admin\PermissionUpdateRequest;
use App\Http\Controllers\Controller;

class PermissionController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PermissionIndexRequest $request)
    {
        $permissions = Permission::query();

        // Search
        if ($request->filled('search')) {
            $permissions->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('guard_name', 'like', "%{$request->search}%");
            });
        }

        // Safe Sorting
        $allowedFields = ['name', 'guard_name' ];
        $allowedOrders = ['asc', 'desc'];

        $field = in_array($request->field, $allowedFields)
            ? $request->field
            : 'name';

        $order = in_array($request->order, $allowedOrders)
            ? $request->order
            : 'asc';

        $permissions->orderBy($field, $order);

        return Inertia::render('Admin/Permission/Index', [
            'title' => 'Permission',
            'filters' => $request->only(['search', 'field', 'order']),
            'permissions' => $permissions->paginate(10)->withQueryString(),
        ]);
    }


    public function store(PermissionStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $permission = Permission::create([
                'name'          => $request->name
            ]);
            DB::commit();
            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission created successfully');
            // return back()->with('success', $permission->name. ' created successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error creating ' .  $th->getMessage());
        }
    }

    public function update(PermissionUpdateRequest $request, Permission $permission)
    {
        DB::beginTransaction();
        try {
            $permission->update([
                'name'          => $request->name
            ]);
            DB::commit();
            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission updated successfully');
            // return back()->with('success',  $permission->name. ' updated successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error updating ' .  $th->getMessage());
        }
    }

    public function destroy(Permission $permission)
    {
            DB::beginTransaction();
            try {
                $permission->delete();
                DB::commit();
                return redirect()
                    ->route('admin.permissions.index')
                    ->with('success', 'Permission deleted successfully');
                // return back()->with('success', $permission->name. ' deleted successfully.');
            } catch (\Throwable $th) {
                DB::rollback();
                return back()->with('error', 'Error deleting ' . $permission->name . $th->getMessage());
            }
    }
}
