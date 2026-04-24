<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ProductIndexRequest — validates all query-string parameters for the product listing.
 *
 * WHY a FormRequest for a GET request?
 *  - Without it, raw ?search=<script> or ?category_id=0;DROP+TABLE would silently
 *    reach the service and potentially cause SQL injection or LIKE-based DoS.
 *  - Centralises validation in one testable class — the controller stays clean.
 *  - `sometimes` on every field means the rule only applies when the key is present:
 *    sending no query string is valid and the controller uses sensible defaults.
 *
 * Sorting params (field, order, perPage) — existed before.
 * Filter params (search, category_id, brand_id, price_from, price_to, published, in_stock) — new.
 */
class ProductIndexRequest extends FormRequest
{
    /**
     * Admin routes are already guarded by `auth` + `role:admin` middleware.
     * Any request that reaches here is authenticated and authorised.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Sorting ──────────────────────────────────────────────────────────
            // `in:` whitelist prevents arbitrary column names reaching orderBy().
            // A user crafting ?field=password would otherwise sort on a non-existent column
            // or expose data ordering based on sensitive fields.
            'field' => [
                'sometimes',
                'in:title,slug,in_stock,price,quantity,discount_price,published,created_at',
            ],

            'order' => [
                'sometimes',
                'in:asc,desc', // only two valid SQL sort directions
            ],

            'perPage' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100', // hard cap — prevents SELECT * with LIMIT 99999
            ],

            // ── Search ───────────────────────────────────────────────────────────
            // `nullable` allows ?search= (empty string) without an error — treated as "no search".
            // `max:255` prevents extremely long LIKE patterns from stressing MySQL.
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],

            // ── Relational filters ────────────────────────────────────────────────
            // `exists:categories,id` validates against the DB — a non-existent category_id
            // would return 0 results; failing early gives the user a clear error message.
            // `nullable` allows the "All categories" option (sends nothing or empty value).
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],

            // ── Price range ───────────────────────────────────────────────────────
            // `numeric` allows decimals (9.99), unlike `integer`.
            // `min:0` prevents negative prices being used as a filter.
            // price_to is independently validated — cross-field ordering (from < to)
            // is enforced by the UI; the scope handles reversed values gracefully.
            'price_from' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_to' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            // ── Boolean flags ─────────────────────────────────────────────────────
            // `boolean` rule accepts: true, false, 1, 0, "1", "0".
            // `nullable` allows "All" option (sends no value → null → scope skipped).
            // `sometimes` + `nullable` together mean: field absent = no filter applied.
            'published' => ['sometimes', 'nullable', 'boolean'],
            'in_stock' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
