<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * BrandService — owns all business logic for the Brand module.
 *
 * Controllers are thin HTTP handlers: validate → call one service method → respond.
 * All query-building, file I/O, and domain rules live here so they're reusable
 * from CLI commands, Jobs, and tests without going through an HTTP request.
 */
class BrandService
{
    /**
     * Return a paginated list of brands, newest first.
     *
     * paginate() issues COUNT + SELECT with LIMIT/OFFSET — more efficient than
     * fetching all rows. withQueryString() preserves ?search= and ?status= filters
     * across page links so the user doesn't lose their filter when paginating.
     */
    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return Brand::query()
            ->latest()           // ORDER BY created_at DESC
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new brand record.
     *
     * WHY receive UploadedFile separately from $data?
     *  - validated() strips file objects — they aren't DB columns.
     *  - Explicit parameter keeps the signature clear and makes testing easier.
     *
     * WHY no manual Str::slug()?
     *  - Brand model uses Spatie HasSlug, which hooks into the Eloquent `creating`
     *    event and auto-generates a unique slug from `name`.
     */
    public function store(array $data, ?UploadedFile $image): Brand
    {
        if ($image) {
            // store() returns the relative path (e.g. "brands/logo.jpg") which we
            // persist to the DB. The full public URL is built in BrandResource.
            $data['image'] = $image->store('brands', 'public');
        }

        return Brand::create($data);
    }

    /**
     * Update an existing brand record.
     *
     * WHY delete the old image before storing the new one?
     *  - The replaced file becomes an orphan — it consumes disk space (or S3 costs)
     *    indefinitely if not removed.
     *
     * WHY set $data['image'] = $brand->image when no new file?
     *  - validated() does NOT include `image` when no file was uploaded. Without this
     *    line, update() would overwrite image with null, clearing the existing logo.
     */
    public function update(Brand $brand, array $data, ?UploadedFile $image): Brand
    {
        if ($image) {
            // Delete the old logo from disk before uploading the replacement
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }

            $data['image'] = $image->store('brands', 'public');
        } else {
            // No new file — preserve the existing image path in the update payload
            $data['image'] = $brand->image;
        }

        $brand->update($data);

        return $brand;
    }

    /**
     * Soft-delete a brand.
     *
     * WHY NOT delete the logo on soft-delete?
     *  - Soft deletes are reversible. Deleting the file would break the image URL
     *    if the brand is restored. Keep files until a hard (force) delete.
     *
     * WHY NOT cascade-delete products?
     *  - The FK is nullOnDelete — soft-deleting a brand does not touch products.
     *    Products retain brand_id = (the soft-deleted brand's id) and can be
     *    re-associated if the brand is restored.
     */
    public function destroy(Brand $brand): void
    {
        // SoftDeletes: writes deleted_at = NOW(), excludes from standard queries
        $brand->delete();
    }
}
