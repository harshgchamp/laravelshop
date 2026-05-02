<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';

defineProps({
    roles: Object,
});

const confirm = useConfirm();

const destroy = (row) => {
    confirm.require({
        message: `Delete role "${row.name}"? Users assigned this role will lose it.`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.roles.destroy', row.id));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Roles</h2>
                <Link :href="route('admin.roles.create')">
                    <Button label="New" icon="pi pi-plus" />
                </Link>
            </div>

            <DataTable :value="roles.data">
                <Column header="#">
                    <template #body="slotProps">
                        {{
                            (roles.meta.current_page - 1) * roles.meta.per_page +
                            slotProps.index +
                            1
                        }}
                    </template>
                </Column>

                <Column field="name" header="Name" />
                <Column field="guard_name" header="Guard" />

                <!-- Permission count badge -->
                <Column header="Permissions">
                    <template #body="slotProps">
                        <span
                            class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300"
                        >
                            {{ slotProps.data.permissions_count }}
                        </span>
                    </template>
                </Column>

                <Column field="created_at" header="Created" />

                <Column header="Actions">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.roles.edit', slotProps.data.id)">
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
            <div v-if="roles.meta.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    {{ (roles.meta.current_page - 1) * roles.meta.per_page + 1 }}–{{
                        Math.min(roles.meta.current_page * roles.meta.per_page, roles.meta.total)
                    }}
                    of {{ roles.meta.total }}
                </p>

                <div class="flex gap-1">
                    <button
                        v-for="link in roles.meta.links"
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
