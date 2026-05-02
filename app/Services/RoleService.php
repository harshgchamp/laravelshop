<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('permissions')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): Role
    {
        $role = Role::create(['name' => $data['name']]);

        if (! empty($data['permissions'])) {
            // syncPermissions() expects names or model instances, not IDs.
            // Fetch models by ID so Spatie can match guard_name correctly.
            $role->syncPermissions(
                Permission::whereIn('id', $data['permissions'])->get(),
            );
        }

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);

        // Empty array clears all permissions; non-empty resolves IDs to models first.
        $permissions = ! empty($data['permissions'])
            ? Permission::whereIn('id', $data['permissions'])->get()
            : [];

        $role->syncPermissions($permissions);

        return $role;
    }

    public function destroy(Role $role): void
    {
        $role->delete();
    }
}
