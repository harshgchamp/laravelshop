<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionIndexRequest;
use App\Http\Requests\Admin\PermissionStoreRequest;
use App\Http\Requests\Admin\PermissionUpdateRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function index(PermissionIndexRequest $request): Response
    {
        $validated = $request->validated();

        $field = $validated['field'] ?? 'name';
        $order = $validated['order'] ?? 'asc';
        $perPage = (int) ($validated['perPage'] ?? 2);
        $filters = ['search' => $validated['search'] ?? null];

        return Inertia::render('Admin/Permission/Index', [
            'filters' => $request->only(['search', 'field', 'order']),
            'permissions' => $this->permissionService->list($field, $order, $perPage, $filters),
        ]);
    }

    public function store(PermissionStoreRequest $request): RedirectResponse
    {
        $this->permissionService->store($request->validated());

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissionService->update($permission, $request->validated());

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->permissionService->destroy($permission);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
