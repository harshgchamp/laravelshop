<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private const SORTABLE_FIELDS = ['name', 'email', 'created_at', 'updated_at'];

    public function list(
        string $field = 'created_at',
        string $order = 'desc',
        int $perPage = 10,
    ): LengthAwarePaginator {
        $sortField = in_array($field, self::SORTABLE_FIELDS, strict: true) ? $field : 'created_at';
        $sortOrder = $order === 'asc' ? 'asc' : 'desc';

        return User::query()
            ->with('roles')
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => ! empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
