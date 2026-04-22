<script setup> 

import { watch, ref } from 'vue'
import { useForm } from '@inertiajs/vue3' 

import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import InputSwitch from 'primevue/inputswitch'
import FileUpload from 'primevue/fileupload' 
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const props = defineProps({
    category: {
        type: Object,
        default: null
    },
    submitUrl: String,     // route URL
    method: String         // post or put
})
 

// Initialize Form

const form = useForm({
    name: '',
    description: '',
    image: null,
    status: true,
    methodText: 'Created',
    _method: props.method === 'put' ? 'put' : 'post',
})
 

const preview = ref(null)   

watch(
    () => props.category,
    (category) => {
        if (category) {
            form.name = category.name
            form.description = category.description
            form.status = Boolean(category.status)
            preview.value = category.image
            form.methodText = 'Updated'
        }
    },
    { immediate: true }
)

const onImageSelect = (event) => {
    const file = event.files[0]
    form.image = file
    preview.value = URL.createObjectURL(file)
}
 
const submit = () => { 
    form.post(props.submitUrl, {
        forceFormData: true, // required for file upload
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Created', detail: 'Category ' + form.methodText + ' successfully', life: 3000 })
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to ' + form.methodText + ' category', life: 3000 })
        }
    })
}

 
</script>

<template>
<div class="card bg-white dark:bg-gray-800 p-6 rounded-lg shadow">

    <!-- Name -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Name
        </label>
        <InputText v-model="form.name" class="w-full" />
        <small v-if="form.errors.name" class="text-red-500">
            {{ form.errors.name }}
        </small>
    </div>

    <!-- Description -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Description
        </label>
        <Textarea v-model="form.description" rows="4" class="w-full" />
    </div>

    <!-- Image Upload -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Image
        </label>

        <FileUpload
            mode="basic"
            accept="image/*"
            :auto="false"
            chooseLabel="Choose Image"
            @select="onImageSelect"
        />

        <!-- Preview -->
        <div v-if="preview" class="mt-3">
            <img :src=  "preview"
                 class="h-32 rounded shadow object-cover" />
        </div>

        <small v-if="form.errors.image" class="text-red-500">
            {{ form.errors.image }}
        </small>
    </div>

    <!-- Status -->
    <div class="mb-6 flex items-center gap-3">
        <InputSwitch v-model="form.status" />
        <span class="text-gray-700 dark:text-gray-200">
            Active
        </span>
    </div>

    <!-- Submit Button -->
    <Button
        label="Save"
        icon="pi pi-check"
        :loading="form.processing"
        @click="submit"
    />
</div>
</template>
