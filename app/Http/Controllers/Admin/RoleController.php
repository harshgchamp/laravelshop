<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleStoreRequest;
use App\Http\Requests\Admin\RoleUpdateRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Permission;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => RoleResource::collection($this->roleService->list()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create', [
            'permissions' => Permission::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $this->roleService->store($request->validated());

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        // Eager-load permissions so RoleResource can include the permissions[] array
        $role->load('permissions');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => (new RoleResource($role))->resolve(),
            'permissions' => Permission::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->destroy($role);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
