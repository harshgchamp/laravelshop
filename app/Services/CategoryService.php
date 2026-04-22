<?php

declare(strict_types=1); // Enforce strict type checking — catches type-coercion bugs at compile time

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * CategoryService
 *
 * Owns all business logic for the Category module.
 * Controllers stay thin: they validate input (FormRequest), call one service
 * method, then redirect. No query-building or file-handling in controllers.
 *
 * WHY a service class?
 *  - A controller's job is HTTP: read request → return response.
 *  - A service's job is the domain: apply rules, persist data, handle side-effects.
 *  - Separation means you can call CategoryService from a CLI command, a Job, or
 *    a test without going through an HTTP request at all.
 */
class CategoryService
{
    /**
     * Return a paginated list of categories, newest first.
     *
     * WHY paginate() + withQueryString()?
     *  - paginate() issues a COUNT query + a SELECT with LIMIT/OFFSET — more efficient than
     *    fetching all rows and slicing in PHP.
     *  - withQueryString() appends the current URL's query params (?search=foo) to every
     *    page link so filters survive pagination clicks.
     */
    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return Category::query()
            ->latest()               // ORDER BY created_at DESC — most recently added first
            ->paginate($perPage)     // default 10 rows per page, configurable by caller
            ->withQueryString();     // preserve ?search=&status= across page links
    }

    /**
     * Create a new category record.
     *
     * WHY accept UploadedFile separately from $data?
     *  - validated() strips the file from the array — it's not a DB column.
     *  - Keeping the file object separate makes the method signature explicit and
     *    allows calling from tests without building a full Request instance.
     *
     * WHY not generate the slug here?
     *  - Category model uses Spatie's HasSlug trait, which hooks into the Eloquent
     *    `creating` event and auto-generates the slug from `name`. No manual Str::slug()
     *    needed, and uniqueness (slug-1, slug-2) is handled by Spatie automatically.
     */
    public function store(array $data, ?UploadedFile $image): Category
    {
        if ($image) {
            // Store the uploaded file in storage/app/public/categories/
            // store() returns the relative path (e.g. "categories/xyz.jpg") which we save to DB.
            // The full public URL is built in CategoryResource via asset('storage/' . $this->image).
            $data['image'] = $image->store('categories', 'public');
        }

        // Category::create() triggers Eloquent's creating event → Spatie sets the slug.
        // Only $fillable columns are written — mass-assignment protection is still active.
        return Category::create($data);
    }

    /**
     * Update an existing category record.
     *
     * WHY delete the old image before storing the new one?
     *  - The old file becomes an orphan — it takes up disk space (or S3 costs) forever
     *    if not removed. Always clean up replaced assets.
     *
     * WHY keep $data['image'] = $category->image when no new file?
     *  - validated() does NOT include `image` when no file was uploaded. Without this
     *    line, update() would set image = null (clearing the existing image unintentionally).
     */
    public function update(Category $category, array $data, ?UploadedFile $image): Category
    {
        if ($image) {
            // Delete the old image file from disk before uploading the replacement
            if ($category->image) {
                Storage::disk('public')->delete($category->image); // removes the file, not the DB record
            }

            // Store new file and record its relative path
            $data['image'] = $image->store('categories', 'public');
        } else {
            // No new file was uploaded — preserve the existing image path in the update payload
            $data['image'] = $category->image;
        }

        // update() only writes columns listed in $fillable — safe from mass-assignment
        $category->update($data);

        // Return the updated model so callers can inspect the result if needed
        return $category;
    }

    /**
     * Soft-delete a category.
     *
     * WHY NOT delete the image on soft-delete?
     *  - Soft deletes are reversible. If we delete the file and then restore the category,
     *    the image URL in the DB becomes broken. Keep the file intact.
     *  - Hard deletes (force delete) should clean up images — handled separately if needed.
     *
     * WHY NOT cascade-delete products?
     *  - The FK is set to RESTRICT — deleting a category with products would throw an
     *    IntegrityConstraintViolation. Validation in CategoryRequest should prevent
     *    deleting a non-empty category (future improvement: add `has_products` guard).
     */
    public function destroy(Category $category): void
    {
        // SoftDeletes trait replaces DELETE with UPDATE SET deleted_at = NOW()
        // The record stays in the DB and is excluded from all standard queries
        $category->delete();
    }
}
