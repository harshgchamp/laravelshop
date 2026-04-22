<script setup>
 
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import ProductForm from './Partials/ProductForm.vue' 

defineProps({ 
    categories: Object
})

</script>

<template>
<AdminLayout>
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Create Product
        </h1>
    </div>

    <ProductForm
        :product="null"
        :categories="categories"
        :submit-url="route('admin.products.store')"
        method="post"
    />

</AdminLayout>
</template>
