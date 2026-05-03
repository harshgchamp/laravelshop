<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * UserCreatedByAdmin
 *
 * Fired by UserService::store() immediately after a new user is persisted
 * and a role is assigned.
 *
 * WHY an event instead of calling the listener directly?
 *  - The service doesn't need to know WHO cares about this action.
 *  - Adding a new side effect (e.g. Slack notification) means adding one listener —
 *    the service and every other existing listener are untouched.
 *
 * Data carried on the event object:
 *  - $user          → the freshly created User model
 *  - $performedBy   → the admin User who pressed "Save" (for the activity log)
 *  - $assignedRole  → role name string (e.g. 'editor') stored on the event so
 *                     listeners don't need to re-query the user's roles
 *
 * WHY readonly properties?
 *  - Events are value objects — they describe something that ALREADY happened.
 *    Readonly ensures no listener accidentally mutates the shared event payload.
 */
class UserCreatedByAdmin
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly User $performedBy,
        public readonly string $assignedRole,
    ) {}
}
