<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

/**
 * CategoryObserver
 *
 * Handles image file cleanup for the Category model.
 * Categories don't have audit columns (created_by etc.) so this observer
 * is solely responsible for keeping storage clean.
 *
 * WHY handle image deletion here instead of in CategoryService?
 *  - The service's update() already handles "new file → store it". But if image
 *    cleanup lived only in the service, a direct $category->update() call anywhere
 *    else (CLI, seeder, another service) would silently orphan the old file on disk.
 *  - The observer fires on EVERY update regardless of call site — one place, automatic.
 *
 * REGISTERED IN: App\Providers\AppServiceProvider::boot()
 */
class CategoryObserver
{
    /**
     * Fires just BEFORE a Category row is UPDATEd.
     *
     * isDirty('image') — true when the in-memory value differs from the DB value.
     * getOriginal('image') — the value that's currently stored in the DB.
     *
     * We delete the OLD file here (before the UPDATE runs) so the disk and DB
     * stay in sync: if the UPDATE fails, the old file is already gone, but the DB
     * still points to the old path — which is better than having the DB point to
     * a deleted file silently. In practice, file + DB failures together are rare.
     */
    public function updating(Category $category): void
    {
        if ($category->isDirty('image') && $category->getOriginal('image')) {
            Storage::disk('public')->delete($category->getOriginal('image'));
        }
    }

    /**
     * Fires AFTER a Category is permanently (hard) deleted.
     *
     * WHY forceDeleted and not deleted?
     *  - Categories use SoftDeletes. The `deleted` event fires on soft-delete,
     *    but the record (and its image) should survive a soft-delete so it can be restored.
     *  - `forceDeleted` fires only on $category->forceDelete() — a permanent wipe.
     *    At that point it's safe to remove the file from disk.
     */
    public function forceDeleted(Category $category): void
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
    }
}
