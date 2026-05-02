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
     * Return a paginated, filtered, eager-loaded product list.
     *
     * @param  string  $field  Sort column — validated against SORTABLE_FIELDS whitelist
     * @param  string  $order  'asc' | 'desc'
     * @param  int  $perPage  Rows per page (1–100)
     * @param  array  $filters  Associative array of optional filter values:
     *                          search, category_id, brand_id, price_from, price_to,
     *                          published, in_stock — absent key = no filter applied
     *
     * WHY does the service receive a flat $filters array instead of individual params?
     *  - Adding a new filter field requires only one change here (apply the scope).
     *    If filters were individual params, the controller, service signature, and call
     *    site would all need updating for each addition — three-point changes every time.
     *  - The array is "open": callers that don't care about filters pass [] and get
     *    the full unfiltered list. Old code paths don't break.
     *
     * WHY are scopes applied in the service rather than in the model's newQuery()?
     *  - These are request-scoped filters, not permanent model constraints (like
     *    ->where('deleted_at', null) which SoftDeletes adds globally). They only apply
     *    to the admin listing — the storefront, Cart, or OrderService might query products
     *    differently. Keeping them in the service makes that boundary explicit.
     */
    public function list(
        string $field = 'created_at',
        string $order = 'desc',
        int $perPage = 3,
        array $filters = [],
    ): LengthAwarePaginator {
        // Second-layer whitelist — defence-in-depth even after FormRequest validation
        $sortField = in_array($field, self::SORTABLE_FIELDS, strict: true) ? $field : 'created_at';
        $sortOrder = $order === 'asc' ? 'asc' : 'desc';

        // Extract filter values — missing keys produce null, which each scope ignores (no-op)
        $search = $filters['search'] ?? null;
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $brandId = isset($filters['brand_id']) ? (int) $filters['brand_id'] : null;
        $priceFrom = isset($filters['price_from']) ? (float) $filters['price_from'] : null;
        $priceTo = isset($filters['price_to']) ? (float) $filters['price_to'] : null;

        // WHY array_key_exists instead of isset for booleans?
        //  - isset(false) returns false — meaning "show unpublished" would be skipped.
        //    array_key_exists() returns true even when the value is false, so the scope
        //    correctly receives `false` and filters to published = 0.
        $published = array_key_exists('published', $filters) ? (bool) $filters['published'] : null;
        $inStock = array_key_exists('in_stock', $filters) ? (bool) $filters['in_stock'] : null;

        return Product::query()
            ->with(['category', 'brand']) // prevent N+1 — one extra query per relation, not per row
            // ── Apply scopes ───────────────────────────────────────────────────
            // Each scope is a no-op when its argument is null, so the chain always works
            // regardless of which filters the user actually sent.
            ->search($search)
            ->ofCategory($categoryId)
            ->ofBrand($brandId)
            ->priceRange($priceFrom, $priceTo)
            ->ofPublished($published)
            ->ofInStock($inStock)
            // ── Sort + paginate ────────────────────────────────────────────────
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString(); // preserves all query params (filters + sort) in pagination links
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
