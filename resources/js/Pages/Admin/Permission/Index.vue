<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({
    permissions: Object,
    filters: Object
})

/* ---------------------------------
   STATE
---------------------------------*/
const dialogVisible = ref(false)
const editing = ref(false)
const selectedId = ref(null)
const search = ref(props.filters?.search || '')

const confirm = useConfirm()

const form = useForm({
    name: ''
})

/* ---------------------------------
   Sync search when filters change
---------------------------------*/
watch(() => props.filters?.search, (val) => {
    search.value = val || ''
})

/* ---------------------------------
   Open Create
---------------------------------*/
const openCreate = () => {
    editing.value = false
    selectedId.value = null
    form.reset()
    dialogVisible.value = true
}

/* ---------------------------------
   Open Edit
---------------------------------*/
const openEdit = (row) => {
    editing.value = true
    selectedId.value = row.id
    form.name = row.name
    dialogVisible.value = true
}

/* ---------------------------------
   Submit
---------------------------------*/
const submit = () => {
    if (editing.value) {
        form.put(route('admin.permissions.update', selectedId.value), {
            onSuccess: () => {
                dialogVisible.value = false
                form.reset()
                selectedId.value = null
            }
        })
    } else {
        form.post(route('admin.permissions.store'), {
            onSuccess: () => {
                dialogVisible.value = false
                form.reset()
                selectedId.value = null
            }
        })
    }
}


/* ---------------------------------
   Delete
---------------------------------*/
const destroy = (row) => {
    confirm.require({
        message: `Delete "${row.name}" permission?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.permissions.destroy', row.id))
            dialogVisible.value = false
        }
    })
}

/* ---------------------------------
   Search
---------------------------------*/
const applySearch = () => {
    router.get(route('admin.permissions.index'), {
        search: search.value
    }, {
        preserveState: true,
        replace: true
    })
}

const resetSearch = () => {
    search.value = ''
    router.get(route('admin.permissions.index'), {}, {
        preserveState: true,
        replace: true
    })
}
</script>

<template>
  <AdminLayout>
    <ConfirmDialog />

    <div class="card">
      <div class="flex justify-between mb-4">
        <h2 class="text-xl font-semibold">Permissions</h2>

        <Button label="New Permission" icon="pi pi-plus" @click="openCreate" />
      </div>

      <div class="flex gap-2 mb-4">
        <InputText
          v-model="search"
          placeholder="Search permissions..."
          class="w-64"
          @keyup.enter="applySearch"
        />

        <Button label="Search" icon="pi pi-search" @click="applySearch" />
        <Button label="Reset" icon="pi pi-refresh" @click="resetSearch" />
      </div>

      <DataTable :value="permissions.data" paginator :rows="10">
        <Column header="No">
          <template #body="slotProps">
            {{ (permissions.current_page - 1) * permissions.per_page + slotProps.index + 1 }}
          </template>
        </Column>
        <Column field="name" header="Name" />
        <Column field="guard_name" header="Guard" />
        <Column field="created_at" header="Created At" />

        <Column header="Actions">
          <template #body="slotProps">
            <div class="flex flex-column align-items-center w-full gap-3 border-bottom-1  "> 
            <Button label="Edit" outlined @click="openEdit(slotProps.data)" />
            <Button
              label="Delete"
              outlined
              severity="danger" 
              @click="destroy(slotProps.data)"
            />
          </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog -->
    <Dialog
      v-model:visible="dialogVisible"
      @hide="form.reset(); selectedId = null"
      modal
      :header="editing ? 'Edit Permission' : 'Create Permission'"
      :style="{ width: '400px' }"
    >
      <div class="mb-4">
        <InputText
          v-model="form.name"
          placeholder="Permission name"
          class="w-full"
        />
        <small v-if="form.errors.name" class="text-red-500">
          {{ form.errors.name }}
        </small>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          severity="secondary"
          @click="dialogVisible = false"
        />

        <Button label="Save" :loading="form.processing" @click="submit" />
      </template>
    </Dialog>
  </AdminLayout>
</template>
