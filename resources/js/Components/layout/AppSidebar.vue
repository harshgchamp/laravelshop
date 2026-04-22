<script setup>
/**
 * AppSidebar.vue — Admin navigation sidebar
 *
 * Collapsible sidebar that holds links to all admin sections.
 * Collapse state is local to this component (not persisted) — collapses reset on page refresh.
 * For persistent collapse, save `collapsed` to localStorage or a Pinia store.
 *
 * WHY is `route()` used instead of hardcoded href strings?
 *  - Named routes survive URL changes. If you rename /admin/categories to /admin/catalog,
 *    only the Laravel route definition changes — this file needs no update.
 *  - Ziggy's ZiggyVue plugin (registered in app.js) makes route() available globally
 *    in every Vue template without needing a local import.
 */
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// Local reactive state for sidebar collapsed/expanded toggle.
// `ref()` wraps a primitive value so Vue can track changes and re-render.
const collapsed = ref(false);

// Toggle sidebar width between expanded (w-64) and icon-only (w-20)
const toggle = () => { collapsed.value = !collapsed.value; };
</script>

<template>
<!--
    Dynamic class binding: applies both static classes and the conditional width.
    `transition-all duration-300` animates the width change smoothly.
    Dark mode classes (dark:bg-gray-800) activate when <html class="dark"> is set.
-->
<div :class="[
    'bg-white dark:bg-gray-800 shadow-lg transition-all duration-300',
    collapsed ? 'w-20' : 'w-64',
]">

    <!-- Sidebar header: brand name + collapse toggle button -->
    <div class="p-4 flex justify-between items-center">
        <!-- Hide the "Admin" label when collapsed so only the toggle icon remains -->
        <span v-show="!collapsed" class="font-bold text-lg text-gray-800 dark:text-white">
            Admin
        </span>
        <!-- pi pi-bars: PrimeIcons hamburger menu icon. @click calls the toggle function. -->
        <button @click="toggle" class="pi pi-bars text-gray-600 dark:text-gray-300" />
    </div>

    <!-- Navigation links — each uses Inertia's <Link> for SPA navigation -->
    <nav class="mt-4">

        <!--
            :href="route('dashboard')" generates the full URL for the named route.
            Inertia's <Link> intercepts the click, sends an XHR request, and swaps
            the page component without a full browser reload.
        -->
        <Link :href="route('dashboard')"
            class="flex items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
            <i class="pi pi-home" />
            <!-- v-show hides text without removing the element — the icon stays visible when collapsed -->
            <span v-show="!collapsed" class="ml-2">Dashboard</span>
        </Link>

        <Link :href="route('admin.categories.index')"
            class="flex items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
            <i class="pi pi-tags" />
            <span v-show="!collapsed" class="ml-2">Categories</span>
        </Link>

        <Link :href="route('admin.products.index')"
            class="flex items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
            <i class="pi pi-box" />
            <span v-show="!collapsed" class="ml-2">Products</span>
        </Link>

        <Link :href="route('admin.users.index')"
            class="flex items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
            <i class="pi pi-users" />
            <span v-show="!collapsed" class="ml-2">Users</span>
        </Link>

        <Link :href="route('admin.permissions.index')"
            class="flex items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
            <i class="pi pi-key" />
            <span v-show="!collapsed" class="ml-2">Permissions</span>
        </Link>

    </nav>
</div>
</template>
