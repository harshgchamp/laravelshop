<script setup>
/**
 * Admin/Products/Index.vue — Product listing page
 *
 * Displays all products in a PrimeVue DataTable.
 * Data comes from ProductController@index → ProductResource::collection()
 * → { data: [...], current_page, last_page, per_page, total }.
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

// Link is globally registered in app.js but imported explicitly here for
// IDE autocompletion and to make the dependency immediately visible.
import { router, Link } from '@inertiajs/vue3';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

// `products` shape: ResourceCollection with `data` array + pagination metadata
defineProps({
    products: Object,
});

const confirm = useConfirm();

/**
 * Prompt for confirmation before soft-deleting a product.
 * ProductService::destroy() will also remove the image file from disk.
 */
const destroy = (row) => {
    confirm.require({
        // Products use `title`, not `name` — using row.name would show "undefined"
        message: `Delete "${row.title}" product?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.products.destroy', row.id));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Products</h2>
                <Link :href="route('admin.products.create')">
                    <Button label="New" icon="pi pi-plus" />
                </Link>
            </div>

            <DataTable :value="products.data" paginator :rows="10">
                <Column header="#">
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
                    </template>
                </Column>

                <!-- `title` is the product identifier — products do NOT have a `name` field -->
                <Column field="title" header="Title" />
                <Column field="slug" header="Slug" />
                <Column field="quantity" header="Qty" />

                <!-- Price comes as a float from ProductResource — template displays as-is -->
                <Column field="price" header="Price (£)" />
                <Column field="discount_price" header="Discount (£)" />

                <!-- `published` boolean — controls storefront visibility -->
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
