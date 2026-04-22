<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * ProductService
 *
 * Handles all business logic for the Product module:
 * slug management, image file operations, audit field wiring,
 * and paginated listing with dynamic sorting.
 *
 * The ProductObserver (registered in AppServiceProvider) automatically sets
 * created_by / updated_by / deleted_by — this service does NOT touch those fields.
 * That separation keeps audit logic in one place instead of scattered across services.
 */
class ProductService
{
    /**
     * Columns the admin is allowed to sort by.
     * Defined here (not in the Request) because this is a business rule, not a
     * validation rule — the service decides which sorts are safe to pass to the DB.
     *
     * WHY a whitelist at all?
     *  - If we passed user-supplied column names straight into orderBy(), a malicious
     *    user could craft a sort like `(SELECT password FROM users LIMIT 1)` and trigger
     *    SQL-injection-style data leaks. Always whitelist raw column references.
     */
    private const SORTABLE_FIELDS = [
        'title', 'slug', 'in_stock', 'price',
        'quantity', 'discount_price', 'published', 'created_at',
    ];

    /**
     * Return a paginated, eager-loaded product list with optional dynamic sorting.
     *
     * @param  string  $field    Sort column (validated against SORTABLE_FIELDS)
     * @param  string  $order    'asc' or 'desc'
     * @param  int     $perPage  Rows per page
     */
    public function list(
        string $field   = 'created_at',
        string $order   = 'desc',
        int    $perPage = 10,
    ): LengthAwarePaginator {
        // Reject any field not in the whitelist — fall back to created_at
        $sortField = in_array($field, self::SORTABLE_FIELDS, strict: true) ? $field : 'created_at';

        // Only 'asc' or 'desc' are valid; anything else becomes 'desc'
        $sortOrder = $order === 'asc' ? 'asc' : 'desc';

        return Product::query()
            ->with('category')           // eager-load to prevent N+1: one query for products + one for categories
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();         // append ?field=&order=&perPage= to pagination links
    }

    /**
     * Create a new product record.
     *
     * WHY unset $data['slug'] when empty?
     *  - The Product model uses Spatie HasSlug which hooks into the Eloquent `creating`
     *    event and auto-generates slug from `title` (including unique suffix like -1, -2).
     *  - If we pass slug = null or slug = '' into create(), Spatie detects a non-dirty
     *    slug and may skip generation. Unsetting the key forces Spatie to generate.
     *  - If the user provides a custom slug (validated unique in ProductStoreRequest),
     *    we leave it in $data — Spatie respects an already-set slug value.
     *
     * WHY is created_by NOT set here?
     *  - ProductObserver::creating() sets it automatically on every Product::create() call.
     *    Keeping it in the observer means it can never be forgotten in a new service method.
     */
    public function store(array $data, ?UploadedFile $image): Product
    {
        // Remove slug key if blank so Spatie generates from title
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        if ($image) {
            // Store the file under storage/app/public/products/ and save relative path to DB
            $data['image'] = $image->store('products', 'public');
        }

        // ProductObserver::creating() fires here → sets created_by = Auth::id()
        return Product::create($data);
    }

    /**
     * Update an existing product.
     *
     * WHY is updated_by NOT set here?
     *  - ProductObserver::updating() fires on every $product->update() call and
     *    sets updated_by automatically.
     *
     * WHY preserve $data['image'] = $product->image when no new file?
     *  - validated() omits the `image` key entirely when no file is submitted.
     *    Without this line, update() would set image = null, wiping the stored image.
     */
    public function update(Product $product, array $data, ?UploadedFile $image): Product
    {
        // If slug is blank on update, unset so Spatie re-generates from the new title
        if (isset($data['slug']) && empty($data['slug'])) {
            unset($data['slug']);
        }

        if ($image) {
            // Remove the old image from disk to avoid storage bloat
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $image->store('products', 'public');
        } else {
            // No new image submitted — carry over the existing image path
            $data['image'] = $product->image;
        }

        // ProductObserver::updating() fires here → sets updated_by = Auth::id()
        $product->update($data);

        return $product;
    }

    /**
     * Soft-delete a product and remove its image from disk.
     *
     * WHY delete the image on soft-delete (unlike CategoryService)?
     *  - Products are storage-heavy (multiple images possible). Keeping orphaned images
     *    costs money on S3 at scale. If restore is needed, the admin would re-upload.
     *  - A future improvement: move image to an "archived" S3 prefix instead of deleting.
     *
     * WHY delete the image BEFORE calling delete()?
     *  - After delete() the model is soft-deleted and $product->image is still accessible
     *    in memory, but it's cleaner to release resources before the record is marked gone.
     *
     * WHY is deleted_by NOT set here?
     *  - ProductObserver::deleting() sets deleted_by and calls saveQuietly() before
     *    the soft-delete query runs.
     */
    public function destroy(Product $product): void
    {
        // Release the image file from disk before soft-deleting the record
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // SoftDeletes: sets deleted_at = NOW(), record remains in DB
        // ProductObserver::deleting() fires first → sets deleted_by = Auth::id()
        $product->delete();
    }
}
