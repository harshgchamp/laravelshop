<script setup>
/**
 * Admin/Categories/Edit.vue — Edit an existing category
 *
 * Thin wrapper: renders CategoryForm in "edit" mode with the existing record's data.
 * Props received from CategoryController@edit via Inertia (already transformed by CategoryResource).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import CategoryForm from './Partials/CategoryForm.vue';

// `category` is the CategoryResource array: { id, name, slug, image (full URL), status, created_at }
defineProps({
    category: Object,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Category</h1>
        </div>

        <!--
        :category="category"  → CategoryForm watches this prop and prefills the form fields.
        :submit-url           → PUT route for this specific category (requires the ID).
        method="put"          → CategoryForm sets _method=put in the form data for Laravel
                                method spoofing (required for file uploads via FormData/POST).
    -->
        <CategoryForm
            :category="category"
            :submit-url="route('admin.categories.update', category.id)"
            method="put"
        />
    </AdminLayout>
</template>
