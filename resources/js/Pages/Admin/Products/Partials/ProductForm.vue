<script setup> 

import { watch, ref } from 'vue'
import { useForm } from '@inertiajs/vue3' 

import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import InputSwitch from 'primevue/inputswitch'
import FileUpload from 'primevue/fileupload' 
import { useToast } from 'primevue/usetoast'
import Editor from 'primevue/editor';
import InputNumber from 'primevue/inputnumber';
import InputGroup from 'primevue/inputgroup';
import Select from 'primevue/select'




const toast = useToast()

const props = defineProps({
    product: {
        type: Object,
        default: null
    },
    categories: Object,
    submitUrl: String,     // route URL
    method: String         // post or put
})
 

// Initialize Form

const form = useForm({
    title: '',
    slug: '',
    category_id: '',
    description: '',
    quantity: '',
    in_stock: '',
    price: '',
    discount_price: '',
    image: null,
    published: true,
    methodText: 'Created',
    _method: props.method === 'put' ? 'put' : 'post',
})
 

const preview = ref(null)   

watch(
    () => props.product,
    (product) => {
        if (product) {
                form.title = product.title
                form.slug = product.slug
                form.category_id = product.category_id
                form.description = product.description
                form.quantity = product.quantity
                form.in_stock = product.in_stock
                form.price = product.price
                form.discount_price = product.discount_price
                form.published = Boolean(product.published)
                preview.value = product.image
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
            toast.add({ severity: 'success', summary: 'Created', detail: 'Product ' + form.methodText + ' successfully', life: 3000 })
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to ' + form.methodText + ' product', life: 3000 })
        }
    })
}

 
</script>

<template>
<div class="card bg-white dark:bg-gray-800 p-6 rounded-lg shadow">

    <!-- Title -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Title
        </label>
        <InputText v-model="form.title" class="w-full" />
        <small v-if="form.errors.title" class="text-red-500">
            {{ form.errors.title }}
        </small>
    </div>

    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Slug
        </label>
        <InputText v-model="form.slug" class="w-full" />
        <small v-if="form.errors.slug" class="text-red-500">
            {{ form.errors.slug }}
        </small>
    </div>

    
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Category
        </label>
       
      <Select v-model="form.category_id" :options="categories" optionValue="id" optionLabel="name" placeholder="Select Category" />

        <small v-if="form.errors.category_id" class="text-red-500">
            {{ form.errors.category_id }}
        </small>
    </div>


   <div class="flex gap-4">
    
    <div class="mb-4 flex-1">
        <InputGroup>
            <Button label="Quantity" />
            <InputNumber v-model="form.quantity" inputId="quantity" />
        </InputGroup>

        <small v-if="form.errors.quantity" class="text-red-500">
            {{ form.errors.quantity }}
        </small>
    </div>

    <div class="mb-4 flex-1">
        <InputGroup>
            <Button label="In Stock" />
            <InputNumber v-model="form.in_stock" inputId="in_stock" />
        </InputGroup>

        <small v-if="form.errors.in_stock" class="text-red-500">
            {{ form.errors.in_stock }}
        </small>
    </div>

    <div class="mb-4 flex-1">
        <InputGroup>
            <Button label="Price" />
            <InputNumber v-model="form.price" inputId="price" />
        </InputGroup>

        <small v-if="form.errors.price" class="text-red-500">
            {{ form.errors.price }}
        </small>
    </div>

    <div class="mb-4 flex-1">
        <InputGroup>
            <Button label="Discount Price" />
            <InputNumber v-model="form.discount_price" inputId="discount_price" />
        </InputGroup>

        <small v-if="form.errors.discount_price" class="text-red-500">
            {{ form.errors.discount_price }}
        </small>
    </div>

</div>

    <!-- Description -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Description
        </label> 
        <Editor v-model="form.description" rows="4" class="w-full" editorStyle="height: 200px" />
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
        <InputSwitch v-model="form.published" />
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
