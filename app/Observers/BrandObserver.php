<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

/**
 * BrandObserver
 *
 * Keeps storage clean for the Brand model — deletes orphaned image files
 * when an image is replaced or a brand is permanently deleted.
 *
 * Same pattern as CategoryObserver. Brands also use SoftDeletes, so:
 *  - `updating`     → clean up the old image when image field changes
 *  - `forceDeleted` → clean up the image on permanent hard-delete
 *
 * REGISTERED IN: App\Providers\AppServiceProvider::boot()
 */
class BrandObserver
{
    public function updating(Brand $brand): void
    {
        if ($brand->isDirty('image') && $brand->getOriginal('image')) {
            Storage::disk('public')->delete($brand->getOriginal('image'));
        }
    }

    public function forceDeleted(Brand $brand): void
    {
        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }
    }
}
