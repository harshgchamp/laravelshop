<script setup>
/**
 * Admin/Users/Index.vue — User listing page
 *
 * Displays all users in a DataTable. Data from UserController@index → UserResource::collection.
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

defineProps({
    users: Object, // ResourceCollection: { data: [...], current_page, last_page, ... }
});

const confirm = useConfirm();

const destroy = (row) => {
    confirm.require({
        message: `Delete user "${row.name}"?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.users.destroy', row.id));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Users</h2>
                <Link :href="route('admin.users.create')">
                    <Button label="New" icon="pi pi-plus" />
                </Link>
            </div>

            <DataTable :value="users.data" paginator :rows="10">
                <Column header="#">
                    <template #body="slotProps">{{ slotProps.index + 1 }}</template>
                </Column>

                <Column field="name" header="Name" />
                <Column field="email" header="Email" />

                <!-- created_at is formatted as 'Y-m-d' by UserResource -->
                <Column field="created_at" header="Joined" />

                <Column header="Actions">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.users.edit', slotProps.data.id)">
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
