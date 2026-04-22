<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import { router, Link } from '@inertiajs/vue3'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useConfirm } from 'primevue/useconfirm'

defineProps({
    users: Object
})

const confirm = useConfirm()

const destroy = (row) => {
    confirm.require({
        message: 'Delete this ' + "`" + `${row.name}` + "`" + ' user?',
        accept: () => {
            router.delete(route('admin.users.destroy', row.id))
        }
    })
}
</script>

<template>
<AdminLayout>
    <div class="card">
        <div class="flex justify-between mb-4">
            <h2 class="text-xl font-semibold">Users</h2>

            <!-- Navigate to Create Page -->
            <Link :href="route('admin.users.create')">
                <Button label="New" icon="pi pi-plus" />
            </Link>
        </div>

        <DataTable :value="users.data" paginator :rows="10">
            <Column header="No">
                <template #body="slotProps">
                    {{slotProps.index + 1}}
                </template>
            </Column>
            <Column field="name" header="Name" />
            <Column field="email" header="Email" />
            <Column field="created_at" header="Created" /> 
 

            <Column header="Actions">
                <template #body="slotProps">
                    <div class="flex flex-column align-items-center w-full gap-3 border-bottom-1 surface-border">
                    <!-- Navigate to Edit Page -->
                    <Link :href="route('admin.users.edit', slotProps.data.id)">
                        <Button label="Edit" outlined />
                    </Link>

                    <Button label="Delete" outlined severity="danger" @click="destroy(slotProps.data)" />
                    </div>
                </template>
            </Column>
        </DataTable>

    </div>
</AdminLayout>
</template>
