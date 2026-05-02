<script setup>
/**
 * Admin/Brands/Create.vue — Create a new brand
 *
 * Thin wrapper: passes null brand into BrandForm (create mode).
 * No props from the controller — the create form is self-contained.
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import BrandForm from './Partials/BrandForm.vue';
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create Brand</h1>
        </div>

        <!--
            :brand="null"  → BrandForm's watch sees null, skips prefill → blank form
            :submit-url   → route to BrandController@store
            method="post" → no _method spoofing needed on create (no file + PUT conflict)
        -->
        <BrandForm :brand="null" :submit-url="route('admin.brands.store')" method="post" />
    </AdminLayout>
</template>
