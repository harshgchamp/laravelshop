<script setup>
 
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import CategoryForm from './Partials/CategoryForm.vue' 
</script>

<template>
<AdminLayout>
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Create Category
        </h1>
    </div>

    <CategoryForm
        :category="null"
        :submit-url="route('admin.categories.store')"
        method="post"
    />

</AdminLayout>
</template>
