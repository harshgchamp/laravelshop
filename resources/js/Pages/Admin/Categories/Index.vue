<script setup>
/**
 * Admin/Categories/Index.vue — Category listing page
 *
 * Displays all categories in a PrimeVue DataTable with pagination and delete confirmation.
 * Data comes from CategoryController@index via Inertia props (already paginated + transformed
 * by CategoryResource into { data: [...], current_page, last_page, ... }).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

// router: Inertia's programmatic navigation — used for DELETE requests
// Link: Inertia's <a> replacement for SPA navigation (globally registered in app.js,
//       but imported explicitly here for clarity and IDE autocompletion)
import { router, Link } from '@inertiajs/vue3';

// PrimeVue DataTable components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';

// useConfirm: returns the ConfirmationService instance (registered in app.js).
// Calling confirm.require() opens the <ConfirmDialog> mounted in AuthenticatedLayout.
import { useConfirm } from 'primevue/useconfirm';

// `categories` is the paginated CategoryResource collection from the controller:
// { data: [...], current_page: 1, last_page: 3, per_page: 10, total: 28, links: [...] }
defineProps({
    categories: Object,
});

const confirm = useConfirm();

/**
 * Show a confirmation dialog before deleting.
 *
 * WHY confirm before delete?
 *  - Soft deletes are reversible but only via code/tinker — there's no "restore" UI yet.
 *    A confirmation step prevents accidental data loss.
 *
 * WHY router.delete() instead of a form submit?
 *  - Inertia's router.delete() sends a DELETE XHR with the CSRF token automatically,
 *    then re-fetches the current page props — the table updates without a full reload.
 */
const destroy = (row) => {
    confirm.require({
        message: `Delete "${row.name}" category?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        // `accept` callback runs only if the user clicks the confirm button
        accept: () => {
            router.delete(route('admin.categories.destroy', row.id));
        },
    });
};
</script>

<template>
<AdminLayout>
    <div class="card">

        <!-- Page header: title + "New" button -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Categories</h2>

            <!--
                <Link> renders as <a> and triggers an Inertia visit (XHR) on click.
                Wrapping <Button> in <Link> keeps PrimeVue's button styling while
                using Inertia's navigation instead of a full page load.
            -->
            <Link :href="route('admin.categories.create')">
                <Button label="New" icon="pi pi-plus" />
            </Link>
        </div>

        <!--
            :value="categories.data" — DataTable receives the `data` array from the
            Laravel paginator (ResourceCollection unwraps to { data, links, meta }).

            paginator + :rows="10" — DataTable's CLIENT-SIDE paginator. Note: since
            the controller already paginates at 10, this is a UI-only paginator over
            the current page's data. For true server-side pagination, use
            @page="onPage" and re-request the server.
        -->
        <DataTable :value="categories.data" paginator :rows="10">

            <!-- Row number: slotProps.index is 0-based within the current page -->
            <Column header="#">
                <template #body="slotProps">
                    {{ slotProps.index + 1 }}
                </template>
            </Column>

            <!-- field="name" / field="slug" — DataTable reads directly from each row object -->
            <Column field="name" header="Name" />
            <Column field="slug" header="Slug" />

            <!-- Boolean status column — displays human-readable "Active" / "Inactive" -->
            <Column header="Status">
                <template #body="slotProps">
                    <span
                        :class="slotProps.data.status
                            ? 'text-green-600 font-medium'
                            : 'text-gray-400'"
                    >
                        {{ slotProps.data.status ? 'Active' : 'Inactive' }}
                    </span>
                </template>
            </Column>

            <!-- Actions: Edit (navigate) + Delete (confirm then destroy) -->
            <Column header="Actions">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.categories.edit', slotProps.data.id)">
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
