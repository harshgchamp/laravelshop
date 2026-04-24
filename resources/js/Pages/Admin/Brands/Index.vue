<script setup>
/**
 * Admin/Brands/Index.vue — Brand listing page
 *
 * Displays all brands in a PrimeVue DataTable with pagination and delete confirmation.
 * Data comes from BrandController@index via Inertia props (paginated + transformed
 * by BrandResource into { data: [...], current_page, last_page, ... }).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

// router: Inertia's programmatic navigation — used for DELETE requests
// Link: Inertia's <a> replacement for SPA navigation
import { router, Link } from '@inertiajs/vue3';

// PrimeVue DataTable components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';

// useConfirm: accesses the ConfirmationService registered in app.js.
// confirm.require() opens the <ConfirmDialog> mounted in AuthenticatedLayout.
import { useConfirm } from 'primevue/useconfirm';

// `brands` is the paginated BrandResource collection from the controller:
// { data: [...], current_page: 1, last_page: 2, per_page: 10, total: 15 }
defineProps({
    brands: Object,
});

const confirm = useConfirm();

/**
 * Prompt for confirmation before soft-deleting a brand.
 *
 * WHY confirm before delete?
 *  - Soft deletes are reversible only via code/tinker — no "restore" UI yet.
 *    A confirmation step prevents accidental data loss.
 *
 * WHY router.delete() instead of a form submit?
 *  - Inertia's router.delete() sends a DELETE XHR with the CSRF token automatically,
 *    then re-fetches the current page props — the table updates without a full reload.
 */
const destroy = (row) => {
    confirm.require({
        message: `Delete "${row.name}" brand?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.brands.destroy', row.id));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <!-- Page header: title + "New" button -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Brands</h2>

                <!--
                    <Link> renders as <a> and triggers an Inertia visit (XHR) on click.
                    Wrapping <Button> in <Link> keeps PrimeVue's button styling while
                    using Inertia's navigation instead of a full page load.
                -->
                <Link :href="route('admin.brands.create')">
                    <Button label="New" icon="pi pi-plus" />
                </Link>
            </div>

            <DataTable :value="brands.data">
                <Column header="#">
                    <template #body="slotProps">
                        {{ (brands.current_page - 1) * brands.per_page + slotProps.index + 1 }}
                    </template>
                </Column>

                <!-- Brand logo — shown as a small thumbnail, empty cell if no logo -->
                <Column header="Logo">
                    <template #body="slotProps">
                        <img
                            v-if="slotProps.data.image"
                            :src="slotProps.data.image"
                            :alt="slotProps.data.name"
                            class="h-10 w-10 object-contain rounded"
                        />
                        <span v-else class="text-gray-400 text-sm">—</span>
                    </template>
                </Column>

                <!-- field="name" / field="slug" — DataTable reads directly from each row object -->
                <Column field="name" header="Name" />
                <Column field="slug" header="Slug" />

                <!-- Boolean status column — green "Active" or grey "Inactive" -->
                <Column header="Status">
                    <template #body="slotProps">
                        <span
                            :class="
                                slotProps.data.status
                                    ? 'text-green-600 font-medium'
                                    : 'text-gray-400'
                            "
                        >
                            {{ slotProps.data.status ? 'Active' : 'Inactive' }}
                        </span>
                    </template>
                </Column>

                <Column field="created_at" header="Created" />

                <!-- Actions: Edit (navigate) + Delete (confirm then destroy) -->
                <Column header="Actions">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.brands.edit', slotProps.data.id)">
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

            <!-- ── Pagination ─────────────────────────────────────────────── -->
            <div v-if="brands.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    {{ (brands.current_page - 1) * brands.per_page + 1 }}–{{
                        Math.min(brands.current_page * brands.per_page, brands.total)
                    }}
                    of {{ brands.total }}
                </p>

                <div class="flex gap-1">
                    <button
                        v-for="link in brands.links"
                        :key="link.label"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-1 text-sm rounded border transition-colors',
                            link.active
                                ? 'bg-primary-500 text-white border-primary-500'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                        ]"
                        @click="link.url && router.get(link.url, {}, { preserveScroll: true })"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
