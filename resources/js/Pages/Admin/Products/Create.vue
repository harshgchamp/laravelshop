<script setup>
/**
 * Admin/Products/Create.vue — Create a new product
 *
 * Thin wrapper: passes null product + categories list into ProductForm (create mode).
 * Props received from ProductController@create: categories (id + name only).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import ProductForm from './Partials/ProductForm.vue';

// Both collections are plain id+name pairs — avoids leaking full model data.
// categories: [{ id: 1, name: 'Electronics' }, ...]
// brands:     [{ id: 1, name: 'Nike' }, ...]
defineProps({
    categories: Object,
    brands: Object,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create Product</h1>
        </div>

        <!--
        :product="null"  → ProductForm's watch sees null, skips prefill → blank form
        :categories      → passed to the Select dropdown for category_id selection
        :submit-url      → route to ProductController@store
        method="post"    → no _method spoofing needed on create (no file + PUT conflict)
    -->
        <ProductForm
            :product="null"
            :categories="categories"
            :brands="brands"
            :submit-url="route('admin.products.store')"
            method="post"
        />
    </AdminLayout>
</template>
