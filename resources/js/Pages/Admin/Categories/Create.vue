<script setup>
/**
 * Admin/Categories/Create.vue — Create a new category
 *
 * Thin wrapper: just renders the shared CategoryForm in "create" mode.
 * All form logic (state, validation display, submission) lives in CategoryForm.vue
 * so Create and Edit don't duplicate code.
 *
 * Props received from CategoryController@create: none (create form needs no data).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import CategoryForm from './Partials/CategoryForm.vue';
</script>

<template>
<AdminLayout>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create Category</h1>
    </div>

    <!--
        :category="null"  → tells CategoryForm this is a new record (no data to prefill).
        :submit-url       → the named route URL that CategoryForm will POST to.
        method="post"     → used inside CategoryForm to set Laravel's _method spoofing field.
    -->
    <CategoryForm
        :category="null"
        :submit-url="route('admin.categories.store')"
        method="post"
    />

</AdminLayout>
</template>
