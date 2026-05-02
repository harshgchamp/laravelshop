<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const dialogVisible = ref(false);
const editing = ref(false);
const selectedId = ref(null);
const search = ref(props.filters?.search ?? '');

const confirm = useConfirm();
const form = useForm({ name: '' });

watch(
    () => props.filters?.search,
    (val) => {
        search.value = val ?? '';
    },
);

const openCreate = () => {
    editing.value = false;
    selectedId.value = null;
    form.reset();
    dialogVisible.value = true;
};

const openEdit = (row) => {
    editing.value = true;
    selectedId.value = row.id;
    form.name = row.name;
    dialogVisible.value = true;
};

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

const applySearch = () => {
    router.get(
        route('admin.permissions.index'),
        { search: search.value },
        { preserveState: true, replace: true },
    );
};

const resetSearch = () => {
    search.value = '';
    router.get(route('admin.permissions.index'), {}, { preserveState: true, replace: true });
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Permissions</h2>
                <Button label="New Permission" icon="pi pi-plus" @click="openCreate" />
            </div>

            <!-- Search bar -->
            <div class="flex gap-2 mb-4">
                <InputText
                    v-model="search"
                    placeholder="Search permissions..."
                    class="w-64"
                    @keyup.enter="applySearch"
                />
                <Button label="Search" icon="pi pi-search" @click="applySearch" />
                <Button
                    v-if="search"
                    label="Reset"
                    icon="pi pi-times"
                    severity="secondary"
                    outlined
                    @click="resetSearch"
                />
            </div>

            <!-- PermissionController passes a raw LengthAwarePaginator (not ResourceCollection),
                 so pagination fields are at the top level: permissions.current_page, etc. -->
            <DataTable :value="permissions.data">
                <Column header="#">
                    <template #body="slotProps">
                        {{
                            (permissions.current_page - 1) * permissions.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>

                <Column field="name" header="Name" />
                <Column field="guard_name" header="Guard" />
                <Column field="created_at" header="Created" />

                <Column header="Actions">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Button
                                label="Edit"
                                icon="pi pi-pencil"
                                outlined
                                size="small"
                                @click="openEdit(slotProps.data)"
                            />
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
            <div v-if="permissions.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    {{ (permissions.current_page - 1) * permissions.per_page + 1 }}–{{
                        Math.min(permissions.current_page * permissions.per_page, permissions.total)
                    }}
                    of {{ permissions.total }}
                </p>

                <div class="flex gap-1">
                    <button
                        v-for="link in permissions.links"
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

        <!-- Create / Edit Dialog -->
        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="editing ? 'Edit Permission' : 'Create Permission'"
            :style="{ width: '400px' }"
            @hide="
                form.reset();
                selectedId = null;
            "
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
