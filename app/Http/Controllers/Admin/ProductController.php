<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ProductController
 *
 * HTTP layer only — validates input via FormRequests, delegates business logic
 * to ProductService, and returns Inertia responses or redirects.
 *
 * Audit fields (created_by, updated_by, deleted_by) are set automatically by
 * ProductObserver — this controller and the service never touch them.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    // ─── index ────────────────────────────────────────────────────────────────

    /**
     * Display a paginated product list with optional sorting.
     *
     * ProductIndexRequest validates that `field` is a whitelisted column and
     * `order` is 'asc' or 'desc'. The service applies a second whitelist check
     * as defence-in-depth before passing the value to orderBy().
     *
     * WHY read sort params from the request here and pass to the service?
     *  - The controller owns HTTP input; the service owns query logic.
     *  - The service should not reach into the Request object — that would couple
     *    it to the HTTP layer and break CLI/Job reuse.
     */
    public function index(ProductIndexRequest $request): Response
    {
        $validated = $request->validated();

        // ── Sorting params ──────────────────────────────────────────────────
        $field = $validated['field'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $perPage = (int) ($validated['perPage'] ?? 3);

        // ── Filter params ───────────────────────────────────────────────────
        // Pull only the filter keys — sort/pagination keys are not filters.
        // array_filter with null callback removes null values so the service
        // receives only the filters the user actually sent.
        $filters = array_filter([
            'search' => $validated['search'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'price_from' => $validated['price_from'] ?? null,
            'price_to' => $validated['price_to'] ?? null,
            // Use null-safe coalescing but preserve false explicitly for booleans:
            // If 'published' key is absent → not in $validated → null → removed by array_filter.
            // If 'published' = false → 0/false in $validated → kept by this ternary.
            'published' => array_key_exists('published', $validated) ? $validated['published'] : null,
            'in_stock' => array_key_exists('in_stock', $validated) ? $validated['in_stock'] : null,
        ], fn ($v) => $v !== null);

        $products = $this->productService->list($field, $order, $perPage, $filters);

        return Inertia::render('Admin/Products/Index', [
            'products' => ProductResource::collection($products),
            // Dropdown data for the filter bar — id+name only, no leaked model data
            'categories' => Category::select('id', 'name')->get(),
            'brands' => Brand::select('id', 'name')->get(),
            // Pass active filters back to Vue so the filter inputs are pre-populated
            // on page load (e.g. after a redirect or browser back-navigation).
            'filters' => $validated,
        ]);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    /**
     * Show the blank product creation form.
     *
     * WHY Category::select('id', 'name')?
     *  - The form only needs the category ID (for the FK) and name (for the dropdown label).
     *    Selecting all columns wastes memory and leaks internal category data to the frontend.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::select('id', 'name')->get(),
            'brands' => Brand::select('id', 'name')->get(), // id + name only — avoids leaking full brand data
        ]);
    }

    // ─── store ────────────────────────────────────────────────────────────────

    /**
     * Persist a new product.
     *
     * ProductStoreRequest enforces:
     *  - title required, max 200 chars
     *  - slug optional (auto-generated if blank), must be unique, alpha-dash only
     *  - category_id must exist in the categories table
     *  - price/quantity required with min values
     *  - image optional, max 2 MB, must be an image MIME type
     *
     * ProductObserver::creating() → sets created_by = Auth::id() before INSERT.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Pass the raw UploadedFile separately — validated() strips file objects
        $this->productService->store($data, $request->file('image'));

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    // ─── edit ─────────────────────────────────────────────────────────────────

    /**
     * Show the edit form pre-populated with existing product data.
     *
     * ProductResource transforms image path to a full asset URL and includes the
     * related category via whenLoaded() — but `category` is NOT eager-loaded here
     * (we only need category_id for the select box default value, which is already
     * on the product model itself).
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
            'categories' => Category::select('id', 'name')->get(),
            'brands' => Brand::select('id', 'name')->get(),
        ]);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    /**
     * Apply changes to an existing product.
     *
     * ProductUpdateRequest uses Rule::unique()->ignore($product->id) for the slug
     * field so the current product's own slug doesn't fail the uniqueness check.
     *
     * ProductObserver::updating() → sets updated_by = Auth::id() before UPDATE.
     */
    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $this->productService->update($product, $data, $request->file('image'));

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    /**
     * Soft-delete a product and remove its image from disk.
     *
     * ProductObserver::deleting() → sets deleted_by = Auth::id(), then saveQuietly(),
     * before the SoftDeletes trait writes deleted_at.
     *
     * WHY is image deletion handled in the service, not here?
     *  - Image cleanup is a side-effect of deletion — it belongs with the deletion logic,
     *    not in the HTTP layer. If destroy() is ever called from a Job or CLI command,
     *    the image still gets cleaned up automatically.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->destroy($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
