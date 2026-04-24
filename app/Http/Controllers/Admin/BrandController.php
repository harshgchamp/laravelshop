<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Http\Resources\Admin\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * BrandController — HTTP layer only.
 *
 * Responsibility:
 *  1. Accept a validated request (BrandRequest / route model binding).
 *  2. Call exactly one BrandService method.
 *  3. Return an Inertia response or a redirect.
 *
 * Nothing else belongs here: no Str::slug(), no Storage::disk(), no query building.
 * All of that lives in BrandService, which is testable and reusable in isolation.
 *
 * WHY constructor injection?
 *  - Laravel's IoC container resolves BrandService automatically — no `new BrandService()`.
 *  - `readonly` prevents accidental reassignment of the injected instance.
 */
class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService,
    ) {}

    // ─── index ────────────────────────────────────────────────────────────────

    /**
     * Display a paginated list of brands.
     *
     * WHY BrandResource::collection() instead of returning the paginator directly?
     *  - Transforms raw model data (e.g. converts `image` path to a full public URL).
     *  - Inertia serialises the ResourceCollection as JSON including pagination metadata.
     */
    public function index(): Response
    {
        $brands = $this->brandService->list();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => BrandResource::collection($brands),
        ]);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    /**
     * Show the blank create form.
     * No server data needed — the form is self-contained on the Vue side.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Brands/Create');
    }

    // ─── store ────────────────────────────────────────────────────────────────

    /**
     * Persist a new brand.
     *
     * WHY $request->validated() instead of $request->all()?
     *  - validated() returns ONLY keys declared in BrandRequest::rules().
     *    Extra POST fields (e.g. crafted `is_admin=1`) are silently dropped.
     *
     * WHY pass $request->file('image') separately?
     *  - UploadedFile objects are stripped from validated() — they are not DB columns.
     */
    public function store(BrandRequest $request): RedirectResponse
    {
        $this->brandService->store($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    // ─── edit ─────────────────────────────────────────────────────────────────

    /**
     * Show the edit form pre-populated with existing brand data.
     *
     * WHY route model binding (Brand $brand)?
     *  - Laravel resolves the Brand from the {brand} URL segment and returns 404
     *    if the ID does not exist — no manual findOrFail() required.
     *
     * WHY ->resolve()?
     *  - Converts the Resource to a plain array synchronously so Inertia can serialise
     *    it as page props JSON. Without resolve(), Inertia tries to encode the object.
     */
    public function edit(Brand $brand): Response
    {
        return Inertia::render('Admin/Brands/Edit', [
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    /**
     * Apply changes to an existing brand.
     *
     * Both BrandRequest validation and route model binding run before this method.
     * By the time we're inside update(), data is validated and the model is loaded.
     */
    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brandService->update($brand, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    /**
     * Soft-delete a brand.
     *
     * The row stays in the DB with deleted_at set. Standard queries exclude it automatically.
     * Products with this brand_id are unaffected — the FK is nullOnDelete which applies
     * only on hard/force deletes, not soft deletes.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        $this->brandService->destroy($brand);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}
