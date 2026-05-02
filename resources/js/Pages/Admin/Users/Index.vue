<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

defineProps({
    users: Object,
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

            <DataTable :value="users.data">
                <Column header="#">
                    <template #body="slotProps">
                        {{
                            (users.meta.current_page - 1) * users.meta.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>

                <Column field="name" header="Name" />
                <Column field="email" header="Email" />

                <Column header="Role">
                    <template #body="slotProps">
                        <span
                            v-if="slotProps.data.role"
                            class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                        >
                            {{ slotProps.data.role }}
                        </span>
                        <span v-else class="text-gray-400 text-sm">—</span>
                    </template>
                </Column>

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

            <!-- ── Pagination ─────────────────────────────────────────────── -->
            <div v-if="users.meta.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    {{ (users.meta.current_page - 1) * users.meta.per_page + 1 }}–{{
                        Math.min(users.meta.current_page * users.meta.per_page, users.meta.total)
                    }}
                    of {{ users.meta.total }}
                </p>

                <div class="flex gap-1">
                    <button
                        v-for="link in users.meta.links"
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
