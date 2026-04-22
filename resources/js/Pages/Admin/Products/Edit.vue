<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import CategoryForm from './Partials/ProductForm.vue' 

defineProps({
    product: Object,
    categories: Object
})
</script>

<template>
<AdminLayout>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Edit Category
        </h1>
    </div>

    <CategoryForm
        :product="product"
        :categories="categories"
        :submit-url="route('admin.products.update', product.id)"
        method="put"
    />

</AdminLayout>
</template>
