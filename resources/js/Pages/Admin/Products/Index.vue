<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import { useConfirm } from 'primevue/useconfirm'

defineProps({
    products: Object
})

const confirm = useConfirm()

const destroy = (row) => {
    confirm.require({
        message: 'Delete this ' + "`" + `${row.name}` + "`" + ' product?',
        accept: () => {
            router.delete(route('admin.products.destroy', row.id))
        }
    })
}
</script>

<template>
<AdminLayout>
    <div class="card">
        <div class="flex justify-between mb-4">
            <h2 class="text-xl font-semibold">Products</h2>

            <!-- Navigate to Create Page -->
            <Link :href="route('admin.products.create')">
                <Button label="New" icon="pi pi-plus" />
            </Link>
        </div>

        <DataTable :value="products.data" paginator :rows="10">
            <Column header="No">
                <template #body="slotProps">
                    {{slotProps.index + 1}}
                </template>
            </Column>
            <Column field="title" header="Title" />
            <Column field="slug" header="Slug" />
            <Column field="quantity" header="Quantity" /> 
            <Column field="price" header="Price" />
            <Column field="discount_price" header="Discount Price" />

            <Column header="Active">
                <template #body="slotProps">
                    <span v-if="slotProps.data.published">Yes</span>
                    <span v-else>No</span>
                </template>
            </Column>

            <Column header="Actions">
                <template #body="slotProps">
                    <div class="flex flex-column align-items-center w-full gap-3 border-bottom-1 surface-border">
                    <!-- Navigate to Edit Page -->
                    <Link :href="route('admin.products.edit', slotProps.data.id)">
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
