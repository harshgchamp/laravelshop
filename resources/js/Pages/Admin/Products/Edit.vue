<script setup>
/**
 * Admin/Products/Edit.vue — Edit an existing product
 *
 * Thin wrapper: passes the existing product data + categories list into ProductForm (edit mode).
 * Props received from ProductController@edit via Inertia:
 *   - product: ProductResource array (includes image as full URL, category_id, etc.)
 *   - categories: plain collection [{ id, name }] for the category dropdown
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import ProductForm from './Partials/ProductForm.vue'; // NOT CategoryForm — was a copy-paste bug

defineProps({
    product: Object,
    categories: Object,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Product</h1>
        </div>

        <!--
        :product="product"   → ProductForm's watch sees the record and prefills all fields
        :submit-url          → PUT route for this specific product
        method="put"         → sets _method=put in form data for Laravel method spoofing
    -->
        <ProductForm
            :product="product"
            :categories="categories"
            :submit-url="route('admin.products.update', product.id)"
            method="put"
        />
    </AdminLayout>
</template>
