<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionService
{
    private const SORTABLE_FIELDS = ['name', 'guard_name', 'created_at', 'updated_at'];

    public function list(
        string $field = 'name',
        string $order = 'asc',
        int $perPage = 10,
        array $filters = [],
    ): LengthAwarePaginator {
        $sortField = in_array($field, self::SORTABLE_FIELDS, strict: true) ? $field : 'name';
        $sortOrder = $order === 'asc' ? 'asc' : 'desc';
        $search = $filters['search'] ?? null;

        return Permission::query()
            ->when(
                $search,
                fn ($q) => $q->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('guard_name', 'like', "%{$search}%");
                }),
            )
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): Permission
    {
        return Permission::create(['name' => $data['name']]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update(['name' => $data['name']]);

        return $permission;
    }

    public function destroy(Permission $permission): void
    {
        $permission->delete();
    }
}
