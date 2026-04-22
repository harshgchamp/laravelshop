<script setup>
/**
 * Dashboard.vue — Admin dashboard overview
 *
 * Currently displays three KPI cards: total Users, Categories, and Products.
 * Data comes from web.php's inline route closure — counts are fetched directly
 * via Eloquent and passed as Inertia props.
 *
 * Future improvements (Week 3 of the roadmap):
 *  - Sales chart (revenue over time using Chart.js or PrimeVue Chart)
 *  - Recent orders widget
 *  - Low-stock alerts
 *  - Move the closure into a DashboardController for testability
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';

// Three scalar counts passed from the route closure in web.php
const props = defineProps({
    users: { type: Number, required: true },
    categories: { type: Number, required: true },
    products: { type: Number, required: true },
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard</h1>
        </div>

        <!--
        CSS Grid with 12 columns.
        Each card takes 12 cols on mobile → 6 on large → 4 on extra-large.
        This creates: 1 column on mobile, 2 on tablet, 3 on desktop.
    -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Users KPI card -->
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex justify-between items-center"
                >
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Users</p>
                        <!-- props.users is an integer passed directly from User::count() -->
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ props.users }}
                        </p>
                    </div>
                    <!-- Icon badge: blue circle with users icon -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-400/10"
                    >
                        <i class="pi pi-users text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Categories KPI card -->
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex justify-between items-center"
                >
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            Total Categories
                        </p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ props.categories }}
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-400/10"
                    >
                        <i class="pi pi-tags text-orange-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Products KPI card -->
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex justify-between items-center"
                >
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Products</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ props.products }}
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100 dark:bg-cyan-400/10"
                    >
                        <i class="pi pi-box text-cyan-500 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
