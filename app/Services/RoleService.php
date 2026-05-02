<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): Role
    {
        $role = Role::create(['name' => $data['name']]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    public function destroy(Role $role): void
    {
        $role->delete();
    }
}
