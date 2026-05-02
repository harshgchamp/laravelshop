<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RoleAssignedToUser;
use App\Events\UserCreatedByAdmin;
use App\Models\ActivityLog;

/**
 * LogAdminActivity — Synchronous Listener
 *
 * Writes a row to activity_logs for every admin action event.
 * This listener is intentionally SYNCHRONOUS (no ShouldQueue) so the
 * audit record is written in the same DB transaction as the HTTP request.
 * If the log write fails, we let it bubble — you never want silent audit gaps.
 *
 * HOW ONE LISTENER HANDLES MULTIPLE EVENTS:
 *  PHP 8 union types in handle() let a single method accept different event classes.
 *  Laravel's event dispatcher checks the parameter type at runtime and routes correctly.
 *  Both event types are registered → this listener in EventServiceProvider::$listen.
 *
 * WHY not separate listeners per event?
 *  - All paths do the same thing: write to activity_logs.
 *  - One class is easier to find, test, and reason about than two near-identical ones.
 *  - If the logging logic diverged significantly, splitting would make sense.
 */
class LogAdminActivity
{
    public function handle(UserCreatedByAdmin|RoleAssignedToUser $event): void
    {
        // Build the properties bag based on which event we received
        $properties = match (true) {
            $event instanceof UserCreatedByAdmin => [
                'name'  => $event->user->name,
                'email' => $event->user->email,
                'role'  => $event->assignedRole,
            ],
            $event instanceof RoleAssignedToUser => [
                'name'          => $event->user->name,
                'previous_role' => $event->previousRole,
                'new_role'      => $event->newRole,
            ],
        };

        // Derive a human-readable event name from the class name
        // UserCreatedByAdmin → user.created_by_admin
        // RoleAssignedToUser → role.assigned_to_user
        $eventName = $this->toSnakeDotCase(class_basename($event));

        ActivityLog::create([
            'user_id'      => $event->performedBy->id,
            'event'        => $eventName,
            // subject_type / subject_id = the affected user record
            'subject_type' => $event->user::class,
            'subject_id'   => $event->user->id,
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
        ]);
    }

    /**
     * Convert a PascalCase class name to dot.snake notation.
     * UserCreatedByAdmin → user.created_by_admin
     */
    private function toSnakeDotCase(string $className): string
    {
        // Insert underscore before each uppercase letter, lowercase everything
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        // Replace the first underscore (after the first word) with a dot
        return preg_replace('/_/', '.', $snake, limit: 1);
    }
}
