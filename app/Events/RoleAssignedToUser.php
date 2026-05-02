<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * RoleAssignedToUser
 *
 * Fired by UserService::update() when an admin changes a user's role.
 * Only fires when the role actually changed — not on every profile update.
 *
 * Carries both the old and new role names so the activity log can show
 * a meaningful "changed from editor → admin" message without re-querying.
 */
class RoleAssignedToUser
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly User $performedBy,
        public readonly string $newRole,
        public readonly ?string $previousRole,
    ) {}
}
