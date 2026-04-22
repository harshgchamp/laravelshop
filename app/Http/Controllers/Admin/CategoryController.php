<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CategoryController
 *
 * Responsibility: HTTP only.
 *  1. Accept the request (already validated by CategoryRequest / route model binding).
 *  2. Call one CategoryService method.
 *  3. Return an Inertia response or a redirect.
 *
 * What is NOT here:
 *  - No Str::slug(), no Storage::disk(), no query building.
 *  - All of that lives in CategoryService, which is unit-testable in isolation.
 *
 * WHY constructor injection for the service?
 *  - Laravel's IoC container automatically resolves CategoryService when the controller
 *    is instantiated — no manual `new CategoryService()` needed anywhere.
 *  - In feature tests you can swap the real service for a mock via app()->bind().
 *  - `readonly` prevents accidental reassignment of the injected instance.
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    // ─── index ────────────────────────────────────────────────────────────────

    /**
     * Display the paginated list of categories.
     *
     * WHY CategoryResource::collection() instead of returning the paginator directly?
     *  - Resource transforms raw model data: converts `image` from a relative storage
     *    path ("categories/abc.jpg") to a full public URL via asset().
     *  - Inertia serialises the ResourceCollection as JSON, including pagination metadata
     *    (current_page, last_page, links) — the Vue component uses this for the paginator.
     */
    public function index(): Response
    {
        $categories = $this->categoryService->list();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    /**
     * Show the blank create form.
     * No data needed from the server — the form is self-contained on the Vue side.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    // ─── store ────────────────────────────────────────────────────────────────

    /**
     * Persist a new category.
     *
     * WHY $request->validated() instead of $request->all()?
     *  - validated() returns ONLY the keys declared in CategoryRequest::rules().
     *    Any extra POST fields (e.g. a crafted `is_admin=1`) are silently dropped.
     *    This is the second line of defence after $fillable mass-assignment guard.
     *
     * WHY pass $request->file('image') separately?
     *  - UploadedFile objects are stripped from validated() — they're not DB columns.
     *    Passing the file separately keeps the service signature explicit.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->categoryService->store($data, $request->file('image'));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    // ─── edit ─────────────────────────────────────────────────────────────────

    /**
     * Show the edit form for an existing category.
     *
     * WHY route model binding (Category $category)?
     *  - Laravel resolves the Category from the {category} URL segment automatically,
     *    and returns 404 if the ID doesn't exist — no manual findOrFail() needed.
     *
     * WHY ->resolve() instead of just passing the Resource?
     *  - resolve() converts the Resource to a plain array synchronously so Inertia
     *    can serialise it as part of the page props JSON.
     *    Without resolve(), Inertia would try to JSON-encode the Resource object itself.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    /**
     * Apply changes to an existing category.
     *
     * Both CategoryRequest and route model binding run before this method is called.
     * By the time we're inside update(), the data is validated and the model is loaded.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        $this->categoryService->update($category, $data, $request->file('image'));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    /**
     * Soft-delete a category.
     *
     * WHY soft delete instead of hard delete?
     *  - Soft delete keeps the row (sets deleted_at) so data can be audited or restored.
     *  - Standard queries automatically exclude soft-deleted rows (WHERE deleted_at IS NULL).
     *  - To permanently delete: Category::withTrashed()->find($id)->forceDelete().
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->destroy($category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
