<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * ProductObserver
 *
 * Automatically manages audit columns (created_by, updated_by, deleted_by) on every
 * Product lifecycle event — without polluting controllers or service methods.
 *
 * WHY the Observer pattern?
 *  - Before observers, every controller method manually did $data['created_by'] = auth()->id().
 *    If you add a new service method or CLI command, it's easy to forget.
 *  - The observer is registered once in AppServiceProvider and fires automatically on
 *    EVERY create/update/delete — impossible to forget.
 *
 * WHY not use model events (static boot method)?
 *  - Observers keep the Model file clean and are independently testable.
 *  - boot() closures can't be mocked in tests; Observer classes can.
 *
 * REGISTERED IN: App\Providers\AppServiceProvider::boot()
 */
class ProductObserver
{
    /**
     * Fires just BEFORE a new Product row is INSERTed.
     *
     * WHY `creating` (not `created`)?
     *  - `creating` fires before the INSERT, so the value is included in the
     *    initial INSERT query — one round-trip.
     *  - `created` fires after INSERT, requiring a separate UPDATE — two round-trips.
     */
    public function creating(Product $product): void
    {
        // Auth::id() returns the currently authenticated user's primary key (int|null).
        // The products.created_by FK is NOT nullable, so an unauthenticated call
        // would throw a DB IntegrityConstraintViolation — which is intentional:
        // products should only ever be created by a logged-in user.
        $product->created_by = Auth::id();
    }

    /**
     * Fires just BEFORE an existing Product row is UPDATEd.
     *
     * products.updated_by IS nullable — a product may never have been edited after creation.
     */
    public function updating(Product $product): void
    {
        $product->updated_by = Auth::id();
    }

    /**
     * Fires just BEFORE a Product is soft-deleted.
     *
     * WHY saveQuietly() instead of save()?
     *  - save() would trigger the `updating` event again → `updating` sets updated_by →
     *    which calls save() again → infinite recursion.
     *  - saveQuietly() persists the column change WITHOUT firing any model events,
     *    breaking the loop.
     *
     * WHY not set deleted_by inside the service's destroy() method?
     *  - The observer guarantees it's set even if delete() is called from anywhere
     *    (CLI, Job, another service). Central, automatic, unforgettable.
     */
    public function deleting(Product $product): void
    {
        $product->deleted_by = Auth::id();

        // Persist deleted_by BEFORE the soft-delete UPDATE runs, without triggering events
        $product->saveQuietly();
    }
}
