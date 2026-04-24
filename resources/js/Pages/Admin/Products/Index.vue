<script setup>
/**
 * Admin/Products/Index.vue — Product listing with search and filters
 *
 * Displays all products in a PrimeVue DataTable.
 * A filter bar above the table lets the admin search by keyword, narrow by
 * category/brand, set a price range, and toggle published/in-stock status.
 *
 * Filter flow:
 *  1. Admin fills filter inputs (local `ref` state — no server request yet).
 *  2. Admin clicks "Search" → applyFilters() calls router.get() with the current
 *     filter values as query-string params.
 *  3. Inertia sends a GET XHR to /admin/products?search=...&category_id=...
 *  4. ProductIndexRequest validates the params.
 *  5. ProductController extracts the filters and passes them to ProductService.
 *  6. ProductService chains query scopes (search, ofCategory, priceRange, etc.).
 *  7. Inertia returns JSON with the filtered `products` + the `filters` prop echoed back.
 *  8. This component re-renders with the new data — filter inputs stay populated
 *     because they were initialised from the `filters` prop.
 */
import { ref } from 'vue';
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';

// PrimeVue DataTable
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';

// PrimeVue form components used in the filter bar
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';

import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    // ResourceCollection: { data: [...], current_page, last_page, per_page, total }
    products: Object,

    // Dropdown options for the filter bar — [{ id, name }] from ProductController
    categories: Object,
    brands: Object,

    // Active filters echoed back by the controller so inputs are pre-populated.
    // On a fresh visit: {} — all inputs default to empty/null.
    // After a filter: { search: 'apple', category_id: 2, ... }
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();

// ─── Filter state ──────────────────────────────────────────────────────────────
// Each ref is initialised from props.filters so the inputs survive page re-renders
// (e.g. browser back navigation after visiting an edit page).
//
// WHY separate refs instead of a single reactive object?
//  - Reactive objects flatten nested reactivity in Vue 3 — individual refs are more
//    explicit and easier to reset individually (just assign null back to each).

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? null);
const brandId = ref(props.filters.brand_id ?? null);
const priceFrom = ref(props.filters.price_from ?? null);
const priceTo = ref(props.filters.price_to ?? null);
const published = ref(props.filters.published ?? null); // null = no filter
const inStock = ref(props.filters.in_stock ?? null);

// ── Dropdown options for the Yes/No/All selects ────────────────────────────────
// `null` value = "All" option → the scope ignores it (no WHERE clause added).
// true/false map to WHERE published = 1 / WHERE published = 0 on the server.
const boolOptions = [
    { label: 'All', value: null },
    { label: 'Yes', value: true },
    { label: 'No', value: false },
];

// ─── Filter actions ────────────────────────────────────────────────────────────

/**
 * Build a clean query-string params object and trigger an Inertia GET visit.
 *
 * WHY strip null/empty values before sending?
 *  - A clean URL (?search=apple&category_id=3) is more readable and bookmarkable
 *    than one with empty params (?search=&category_id=null&brand_id=&price_from=).
 *  - The server treats absent keys and null values identically (scope is a no-op).
 *
 * WHY published and in_stock use ternary (? 1 : 0) when non-null?
 *  - Query strings only carry strings. Sending `true` would arrive as the string "true",
 *    which Laravel's `boolean` validation rule does NOT accept (it requires "1"/"0").
 *    Sending 1/0 ensures correct server-side coercion to PHP true/false.
 *
 * WHY router.get() with preserveState + replace?
 *  - preserveState: keeps the current Vue component mounted — the filter inputs don't
 *    reset to empty while the request is in flight (no flash of empty form).
 *  - replace: replaces the browser history entry instead of pushing — pressing the
 *    browser back button skips intermediate filter states and goes to the previous page.
 */
const applyFilters = () => {
    const params = {};

    if (search.value) params.search = search.value;
    if (categoryId.value !== null) params.category_id = categoryId.value;
    if (brandId.value !== null) params.brand_id = brandId.value;
    if (priceFrom.value !== null) params.price_from = priceFrom.value;
    if (priceTo.value !== null) params.price_to = priceTo.value;
    if (published.value !== null) params.published = published.value ? 1 : 0;
    if (inStock.value !== null) params.in_stock = inStock.value ? 1 : 0;

    router.get(route('admin.products.index'), params, {
        preserveState: true,
        replace: true,
    });
};

/**
 * Clear all filter inputs and navigate to the unfiltered product list.
 */
const resetFilters = () => {
    search.value = '';
    categoryId.value = null;
    brandId.value = null;
    priceFrom.value = null;
    priceTo.value = null;
    published.value = null;
    inStock.value = null;

    router.get(route('admin.products.index'), {}, { replace: true });
};

// ─── Delete ────────────────────────────────────────────────────────────────────

/**
 * Prompt for confirmation before soft-deleting a product.
 * ProductService::destroy() also removes the image file from disk.
 */
const destroy = (row) => {
    confirm.require({
        message: `Delete "${row.title}" product?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.products.destroy', row.id));
        },
    });
};

// ─── Computed helpers ──────────────────────────────────────────────────────────

/**
 * Returns true when any filter is currently active.
 * Used to show/highlight the "Reset" button only when needed.
 */
const hasActiveFilters = () =>
    search.value ||
    categoryId.value !== null ||
    brandId.value !== null ||
    priceFrom.value !== null ||
    priceTo.value !== null ||
    published.value !== null ||
    inStock.value !== null;
</script>

<template>
    <AdminLayout>
        <div class="card">
            <!-- ── Page header ──────────────────────────────────────────────── -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Products</h2>
                <Link :href="route('admin.products.create')">
                    <Button label="New" icon="pi pi-plus" />
                </Link>
            </div>

            <!-- ── Filter bar ──────────────────────────────────────────────── -->
            <!--
                The filter bar is a plain flex layout — not a <form> — because submitting
                is triggered manually by the "Search" button via router.get(), not by
                browser form submission. This avoids full-page reloads.
            -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search: keyword search across title + description -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Search
                        </label>
                        <!--
                            @keyup.enter="applyFilters" lets the user press Enter to search
                            without reaching for the Search button — standard UX expectation.
                        -->
                        <InputText
                            v-model="search"
                            placeholder="Title or description…"
                            class="w-full"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Category filter -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Category
                        </label>
                        <!--
                            :show-clear="true" adds an × icon to clear the selection back to null
                            (null = "All categories" → scope is a no-op → no WHERE clause added).
                        -->
                        <Select
                            v-model="categoryId"
                            :options="categories"
                            option-value="id"
                            option-label="name"
                            placeholder="All categories"
                            class="w-full"
                            :show-clear="true"
                        />
                    </div>

                    <!-- Brand filter -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Brand
                        </label>
                        <Select
                            v-model="brandId"
                            :options="brands"
                            option-value="id"
                            option-label="name"
                            placeholder="All brands"
                            class="w-full"
                            :show-clear="true"
                        />
                    </div>

                    <!-- Published filter -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Published
                        </label>
                        <Select
                            v-model="published"
                            :options="boolOptions"
                            option-value="value"
                            option-label="label"
                            class="w-full"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Price from -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Price from (£)
                        </label>
                        <!--
                            InputNumber handles locale-aware numeric formatting.
                            :min="0" prevents negative price filters.
                            :min-fraction-digits="2" shows e.g. "10.00" for clean display.
                        -->
                        <InputNumber
                            v-model="priceFrom"
                            placeholder="0.00"
                            :min="0"
                            :min-fraction-digits="2"
                            :max-fraction-digits="2"
                            class="w-full"
                        />
                    </div>

                    <!-- Price to -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            Price to (£)
                        </label>
                        <InputNumber
                            v-model="priceTo"
                            placeholder="999.99"
                            :min="0"
                            :min-fraction-digits="2"
                            :max-fraction-digits="2"
                            class="w-full"
                        />
                    </div>

                    <!-- In Stock filter -->
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            In Stock
                        </label>
                        <Select
                            v-model="inStock"
                            :options="boolOptions"
                            option-value="value"
                            option-label="label"
                            class="w-full"
                        />
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-end gap-2">
                        <Button
                            label="Search"
                            icon="pi pi-search"
                            class="flex-1"
                            @click="applyFilters"
                        />
                        <!--
                            Reset is only visible when at least one filter is active.
                            v-if keeps the DOM clean and signals clearly to the admin
                            that filters are currently applied.
                        -->
                        <Button
                            v-if="hasActiveFilters()"
                            label="Reset"
                            icon="pi pi-times"
                            severity="secondary"
                            outlined
                            class="flex-1"
                            @click="resetFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- ── Results summary ─────────────────────────────────────────── -->
            <!--
                products.total is injected by LengthAwarePaginator into the ResourceCollection.
                Showing it gives the admin immediate feedback on how many rows match the filters.
            -->
            <p
                v-if="products.total !== undefined"
                class="text-sm text-gray-500 dark:text-gray-400 mb-2"
            >
                {{ products.total }} product{{ products.total === 1 ? '' : 's' }} found
            </p>

            <!-- ── DataTable ───────────────────────────────────────────────── -->
            <DataTable :value="products.data" paginator :rows="10">
                <Column header="#">
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
                    </template>
                </Column>

                <Column field="title" header="Title" />
                <Column field="slug" header="Slug" />

                <!-- Category name — available because service eager-loads `category` -->
                <Column header="Category">
                    <template #body="slotProps">
                        <span class="text-sm">{{ slotProps.data.category?.name ?? '—' }}</span>
                    </template>
                </Column>

                <!-- Brand name — available because service eager-loads `brand` -->
                <Column header="Brand">
                    <template #body="slotProps">
                        <span class="text-sm">{{ slotProps.data.brand_name ?? '—' }}</span>
                    </template>
                </Column>

                <Column field="quantity" header="Qty" />
                <Column field="price" header="Price (£)" />
                <Column field="discount_price" header="Discount (£)" />

                <!-- Published badge — coloured text, same as Categories/Index -->
                <Column header="Published">
                    <template #body="slotProps">
                        <span
                            :class="
                                slotProps.data.published
                                    ? 'text-green-600 font-medium'
                                    : 'text-gray-400'
                            "
                        >
                            {{ slotProps.data.published ? 'Yes' : 'No' }}
                        </span>
                    </template>
                </Column>

                <Column header="Actions">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.products.edit', slotProps.data.id)">
                                <Button label="Edit" icon="pi pi-pencil" outlined size="small" />
                            </Link>
                            <Button
                                label="Delete"
                                icon="pi pi-trash"
                                outlined
                                severity="danger"
                                size="small"
                                @click="destroy(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AdminLayout>
</template>
