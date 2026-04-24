<script setup>
/**
 * Admin/Brands/Edit.vue — Edit an existing brand
 *
 * Thin wrapper: passes the existing brand data into BrandForm (edit mode).
 * Props received from BrandController@edit via Inertia:
 *   - brand: BrandResource array (includes image as full URL, status, etc.)
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import BrandForm from './Partials/BrandForm.vue';

defineProps({
    brand: Object,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Brand</h1>
        </div>

        <!--
            :brand="brand"   → BrandForm's watch sees the record and prefills all fields
            :submit-url      → PUT route for this specific brand
            method="put"     → sets _method=put in form data for Laravel method spoofing
        -->
        <BrandForm
            :brand="brand"
            :submit-url="route('admin.brands.update', brand.id)"
            method="put"
        />
    </AdminLayout>
</template>
