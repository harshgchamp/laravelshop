<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory;

    // migration has softDeletes() — delete() sets deleted_at, record stays in DB
    use HasSlug;
    use SoftDeletes;     // auto-generates slug from `title` on create/update

    /**
     * Mass-assignable columns.
     *
     * WHY are created_by / updated_by / deleted_by in $fillable?
     *  - Spatie HasSlug writes `slug` via fill() internally, and the ProductObserver
     *    sets the audit fields via direct property assignment (not fill), so technically
     *    they don't need to be in $fillable. They're listed here for explicit clarity:
     *    these columns are intentionally writable by the application layer.
     *
     * WHY NOT put `deleted_at` in $fillable?
     *  - SoftDeletes manages `deleted_at` internally — you never mass-assign it.
     */
    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'brand_id',      // nullable FK — products can exist without a brand assignment
        'description',
        'published',
        'quantity',
        'in_stock',
        'price',
        'discount_price',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Configure Spatie's slug generation.
     *
     * generateSlugsFrom('title') → slug is derived from the product title.
     * uniqueSlugs: if "laptop" exists, Spatie creates "laptop-1", "laptop-2", etc.
     *
     * WHY NOT allowSlugIfRelatedModelDoesNotExist()?
     *  - Not needed here; products always belong to a category.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * A product belongs to one category.
     * FK: products.category_id → categories.id (RESTRICT on delete)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * A product optionally belongs to one brand.
     * FK: products.brand_id → brands.id (nullOnDelete — safe for soft-delete workflows)
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // ─── Query Scopes ─────────────────────────────────────────────────────────
    //
    // Scopes are reusable query fragments that live on the model rather than scattered
    // across controllers, services, or repositories.
    //
    // WHY use `when()` inside every scope instead of a plain `if`?
    //  - `when($condition, $callback)` is a no-op when the condition is falsy — it returns
    //    the query builder unchanged without branching. This lets scopes chain cleanly:
    //
    //    Product::query()->search($term)->ofCategory($id)->priceRange($from, $to)
    //
    //    If $term is null, ->search() returns the same builder — the LIKE clause is never
    //    added. The query stays valid and the calling code stays unconditional.
    //
    // WHY NOT use scopes directly in the controller?
    //  - Scopes belong to the Model (query layer). The service orchestrates them.
    //    Controllers should never build queries.

    /**
     * Scope: keyword search across title and description.
     *
     * WHY wrap in a nested where() closure?
     *  - Without the nested closure, ->where(title LIKE ?)->orWhere(desc LIKE ?) would
     *    OR with ALL other conditions in the query, potentially returning rows that other
     *    scopes (e.g. ofCategory) excluded.
     *  - The closure groups the OR so the final SQL is:
     *    WHERE category_id = 3 AND (title LIKE '%apple%' OR description LIKE '%apple%')
     *
     * LIKE limitation: a full-text search on large tables should use a FULLTEXT index
     * (MySQL MATCH...AGAINST) or an external search engine (Meilisearch, Algolia).
     * For an admin panel with hundreds of products, LIKE is acceptable.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            $term,
            fn (Builder $q) => $q->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            }),
        );
    }

    /**
     * Scope: filter products belonging to a specific category.
     *
     * WHY not just ->where('category_id', $id) in the service?
     *  - If this filter ever needs to change (e.g. include sub-categories via
     *    whereIn or a recursive CTE), the change is made here once — not hunted
     *    across every service and controller that filters by category.
     */
    public function scopeOfCategory(Builder $query, ?int $categoryId): Builder
    {
        return $query->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId));
    }

    /**
     * Scope: filter products belonging to a specific brand.
     * Nullable because brand_id on products is nullable — products can have no brand.
     */
    public function scopeOfBrand(Builder $query, ?int $brandId): Builder
    {
        return $query->when($brandId, fn (Builder $q) => $q->where('brand_id', $brandId));
    }

    /**
     * Scope: filter products within a price range.
     *
     * WHY check `!== null` instead of just `when($from)` for the price bounds?
     *  - `when(0.0, ...)` would be falsy — a price_from of £0 would silently be skipped.
     *    Using `$from !== null` ensures £0 is treated as a valid lower bound.
     *
     * WHY two separate `when()` calls instead of one?
     *  - Either bound can be provided independently. The user may want all products
     *    under £50 (price_to only) or all products over £10 (price_from only).
     *
     * Comparison is on `price` (the base price), not discount_price.
     * If you want to filter on effective price, replace with: COALESCE(discount_price, price).
     */
    public function scopePriceRange(Builder $query, ?float $from, ?float $to): Builder
    {
        return $query
            ->when($from !== null, fn (Builder $q) => $q->where('price', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->where('price', '<=', $to));
    }

    /**
     * Scope: filter by published status.
     *
     * WHY `$published !== null` instead of `when($published)`?
     *  - false is falsy. `when(false, ...)` would skip the clause — exactly the opposite
     *    of what "show only unpublished products" means.
     *    `!== null` treats null as "no filter" and both true/false as active filters.
     */
    public function scopeOfPublished(Builder $query, ?bool $published): Builder
    {
        return $query->when(
            $published !== null,
            fn (Builder $q) => $q->where('published', $published),
        );
    }

    /**
     * Scope: filter by in-stock status.
     * Same null-vs-false reasoning as scopeOfPublished.
     */
    public function scopeOfInStock(Builder $query, ?bool $inStock): Builder
    {
        return $query->when(
            $inStock !== null,
            fn (Builder $q) => $q->where('in_stock', $inStock),
        );
    }
}
