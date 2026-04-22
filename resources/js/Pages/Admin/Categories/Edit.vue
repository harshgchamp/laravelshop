<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import CategoryForm from './Partials/CategoryForm.vue' 

defineProps({
    category: Object
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
        :category="category"
        :submit-url="route('admin.categories.update', category.id)"
        method="put"
    />

</AdminLayout>
</template>
