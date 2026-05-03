<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\RoleAssignedToUser;
use App\Events\UserCreatedByAdmin;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        // Fire the event — listeners decide what to do from here.
        //
        // WHY fire the event AFTER assignRole?
        //  - LogAdminActivity reads the assigned role from $data['role'] on the event object.
        //  - SendWelcomeEmail serializes the $user model (SerializesModels re-fetches from DB
        //    when the queue worker picks it up) — role must be persisted before serialization.
        //
        // Auth::user() returns the currently logged-in admin performing the action.
        // Passing it explicitly avoids the listener having to call Auth::user() itself
        // (which would fail in a queued context where there is no active session).
        UserCreatedByAdmin::dispatch($user, Auth::user(), $data['role']);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        // Capture the current role BEFORE syncing — needed for the RoleAssignedToUser event
        $previousRole = $user->getRoleNames()->first();

        $user->update([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => ! empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        // Only fire the event when the role actually changed — no noise for profile-only edits
        if ($previousRole !== $data['role']) {
            RoleAssignedToUser::dispatch(
                $user,
                Auth::user(),
                $data['role'],
                $previousRole,
            );
        }

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
