<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\RoleAssignedToUser;
use App\Events\UserCreatedByAdmin;
use App\Listeners\LogAdminActivity;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider
 *
 * The single source of truth for event → listener mappings.
 *
 * HOW IT WORKS:
 *  Laravel reads the $listen array at boot time. When an event is dispatched
 *  (via event() or Event::dispatch()), the framework looks up the event class here,
 *  instantiates each registered listener, and calls handle() with the event object.
 *
 * SYNC vs QUEUED:
 *  The provider just maps events to listeners.
 *  Whether a listener runs synchronously or goes to the queue is decided by
 *  whether the listener implements ShouldQueue — nothing changes here.
 *
 *  UserCreatedByAdmin fires TWO listeners:
 *   1. LogAdminActivity  → sync  (writes audit row immediately)
 *   2. SendWelcomeEmail  → queued (email goes to jobs table, sent by worker)
 *
 *  RoleAssignedToUser fires ONE listener:
 *   1. LogAdminActivity  → sync
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        UserCreatedByAdmin::class => [
            LogAdminActivity::class,   // sync — audit record written inline
            SendWelcomeEmail::class,   // queued — email sent by queue worker
        ],

        RoleAssignedToUser::class => [
            LogAdminActivity::class,   // sync
        ],

    ];

    public function boot(): void
    {
        //
    }
}
