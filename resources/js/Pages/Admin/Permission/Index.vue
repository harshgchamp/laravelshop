<script setup>
/**
 * Admin/Permission/Index.vue — Permission management page
 *
 * Unlike Category/Product (which use separate Create/Edit pages),
 * permissions are managed inline via a Dialog modal — keeping the workflow fast
 * since permissions are just a single `name` field.
 *
 * Features:
 *  - Server-side search via Inertia router.get() with ?search= query param
 *  - Create / Edit inside a PrimeVue Dialog (no page navigation needed)
 *  - Delete with ConfirmDialog
 *  - Correct row numbers accounting for current page offset
 */
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

import DataTable  from 'primevue/datatable';
import Column     from 'primevue/column';
import Dialog     from 'primevue/dialog';
import Button     from 'primevue/button';
import InputText  from 'primevue/inputtext';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    // Paginated permission data from PermissionController@index
    permissions: Object,
    // Current filter values echoed back from the controller so the search input stays in sync
    filters: Object,
});

// ─── Local State ─────────────────────────────────────────────────────────────
const dialogVisible = ref(false);
const editing       = ref(false);  // true = edit mode, false = create mode
const selectedId    = ref(null);   // ID of the permission being edited

// Search input mirrors props.filters.search (set by the server after each navigation)
const search = ref(props.filters?.search ?? '');

const confirm = useConfirm();

// useForm with only `name` — permissions are just a name string
const form = useForm({ name: '' });

// ─── Sync search input with server-side filter state ─────────────────────────
// If the user navigates back/forward (browser history), props.filters changes.
// This watch keeps the input in sync with the actual applied filter.
watch(() => props.filters?.search, (val) => {
    search.value = val ?? '';
});

// ─── Dialog Helpers ──────────────────────────────────────────────────────────

// Open dialog in CREATE mode — reset any previous form state
const openCreate = () => {
    editing.value    = false;
    selectedId.value = null;
    form.reset();
    dialogVisible.value = true;
};

// Open dialog in EDIT mode — prefill form with the row's current name
const openEdit = (row) => {
    editing.value    = true;
    selectedId.value = row.id;
    form.name        = row.name;
    dialogVisible.value = true;
};

// ─── Submit (Create or Update) ───────────────────────────────────────────────
/**
 * WHY use form.put() / form.post() directly (no forceFormData)?
 *  - Permissions have no file upload — Inertia can send JSON directly.
 *    No need for multipart/form-data or method spoofing here.
 */
const submit = () => {
    if (editing.value) {
        form.put(route('admin.permissions.update', selectedId.value), {
            onSuccess: () => {
                dialogVisible.value = false;
                form.reset();
                selectedId.value = null;
            },
        });
    } else {
        form.post(route('admin.permissions.store'), {
            onSuccess: () => {
                dialogVisible.value = false;
                form.reset();
            },
        });
    }
};

// ─── Delete ──────────────────────────────────────────────────────────────────
const destroy = (row) => {
    confirm.require({
        message: `Delete "${row.name}" permission?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.permissions.destroy', row.id));
        },
    });
};

// ─── Server-side Search ──────────────────────────────────────────────────────
/**
 * WHY router.get() instead of a form submit for search?
 *  - Inertia's router.get() preserves the SPA feel — only the page props update,
 *    not the full page. preserveState: true keeps Vue component state intact.
 *  - replace: true updates the URL without adding a history entry (no extra back-button step).
 */
const applySearch = () => {
    router.get(
        route('admin.permissions.index'),
        { search: search.value },
        { preserveState: true, replace: true },
    );
};

// Clear search and reload with no filter
const resetSearch = () => {
    search.value = '';
    router.get(
        route('admin.permissions.index'),
        {},
        { preserveState: true, replace: true },
    );
};
</script>

<template>
<AdminLayout>

    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Permissions</h2>
            <Button label="New Permission" icon="pi pi-plus" @click="openCreate" />
        </div>

        <!-- Search bar — triggers server-side filter on Enter or button click -->
        <div class="flex gap-2 mb-4">
            <InputText
                v-model="search"
                placeholder="Search permissions..."
                class="w-64"
                @keyup.enter="applySearch"
            />
            <Button label="Search" icon="pi pi-search" @click="applySearch" />
            <Button label="Reset"  icon="pi pi-refresh" severity="secondary" @click="resetSearch" />
        </div>

        <DataTable :value="permissions.data" paginator :rows="10">

            <!--
                Correct row number across pages:
                (current_page - 1) * per_page gives the offset of the first row on this page.
                Adding slotProps.index gives the position within the current page.
                e.g. page 2, per_page 10, index 0 → row number 11.
            -->
            <Column header="#">
                <template #body="slotProps">
                    {{ (permissions.current_page - 1) * permissions.per_page + slotProps.index + 1 }}
                </template>
            </Column>

            <Column field="name"       header="Name" />
            <Column field="guard_name" header="Guard" />
            <Column field="created_at" header="Created" />

            <Column header="Actions">
                <template #body="slotProps">
                    <div class="flex items-center gap-2">
                        <Button label="Edit"   outlined size="small" @click="openEdit(slotProps.data)" />
                        <Button label="Delete" outlined severity="danger" size="small" @click="destroy(slotProps.data)" />
                    </div>
                </template>
            </Column>

        </DataTable>
    </div>

    <!--
        Dialog — shared modal for both create and edit.
        v-model:visible is PrimeVue's two-way binding for dialog open/close state.
        :header changes dynamically based on `editing` flag.
        @hide resets the form when the dialog is closed (X button or backdrop click).
    -->
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :header="editing ? 'Edit Permission' : 'Create Permission'"
        :style="{ width: '400px' }"
        @hide="form.reset(); selectedId = null"
    >
        <div class="mb-4">
            <InputText
                v-model="form.name"
                placeholder="e.g. edit-products"
                class="w-full"
                @keyup.enter="submit"
            />
            <small v-if="form.errors.name" class="text-red-500 mt-1 block">
                {{ form.errors.name }}
            </small>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="dialogVisible = false" />
            <Button label="Save" :loading="form.processing" @click="submit" />
        </template>
    </Dialog>

</AdminLayout>
</template>
