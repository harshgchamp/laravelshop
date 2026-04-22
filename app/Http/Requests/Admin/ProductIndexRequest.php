<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the optional query-string parameters for the product listing.
 *
 * WHY a FormRequest for a GET/index route?
 *  - It keeps validation logic out of the controller and makes it testable in isolation.
 *  - Without it, a bad ?perPage=abc or ?order=DROP TABLE would silently reach the service.
 *
 * All three fields are optional (sometimes) — sending no query string is perfectly valid
 * and the controller falls back to sensible defaults.
 */
class ProductIndexRequest extends FormRequest
{
    /**
     * Admin routes are already guarded by the `auth` + `role:admin` middleware, so we
     * trust that any authenticated admin is authorised to list products.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `sometimes` means: only validate this field if it is present in the request.
            // Without `sometimes`, an absent field would still be required to pass all rules.
            'field' => [
                'sometimes',
                // `in:` whitelist — prevents arbitrary column names reaching orderBy()
                'in:title,slug,in_stock,price,quantity,discount_price,published,created_at',
            ],

            'order' => [
                'sometimes',
                'in:asc,desc', // only two valid SQL sort directions
            ],

            'perPage' => [
                'sometimes',
                'integer',     // must be a whole number — `numeric` also allows "10.5"
                'min:1',       // at least 1 row per page
                'max:100',     // cap at 100 to prevent loading the whole table in one request
            ],
        ];
    }
}
