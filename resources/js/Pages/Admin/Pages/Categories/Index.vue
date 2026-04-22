<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import CategoryForm from '@/Pages/Admin/Categories/Form.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

defineProps({
    categories: Object
})

const showModal = ref(false)
const selected = ref(null)
const confirm = useConfirm()
const toast = useToast()

const edit = (row) => {
    selected.value = row
    showModal.value = true
}

const destroy = (row) => {
    confirm.require({
        message: 'Delete this category?',
        accept: () => {
            router.delete(route('admin.categories.destroy', row.id))
        }
    })
}
</script>

<template>
<AdminLayout>
    <div class="card">
        <div class="flex justify-between mb-4">
            <h2 class="text-xl font-semibold">Categories</h2>

            <Button label="New" icon="pi pi-plus"
                @click="() => { selected = null; showModal = true }"/>
        </div>

        <DataTable :value="categories.data" paginator :rows="10">
            <Column field="name" header="Name" />
            <Column field="slug" header="Slug" />

            <Column header="Active">
                <template #body="slotProps">
                    <span v-if="slotProps.data.is_active">Yes</span>
                    <span v-else>No</span>
                </template>
            </Column>

            <Column header="Actions">
                <template #body="slotProps">
                    <Button icon="pi pi-pencil"
                        @click="edit(slotProps.data)" />
                    <Button icon="pi pi-trash"
                        severity="danger"
                        @click="destroy(slotProps.data)" />
                </template>
            </Column>
        </DataTable>

        <CategoryForm
            v-if="showModal"
            :category="selected"
            @close="showModal = false"
        />
    </div>
</AdminLayout>
</template>
