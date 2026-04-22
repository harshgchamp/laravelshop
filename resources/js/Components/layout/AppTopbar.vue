<script setup>
/**
 * AppTopbar.vue — Admin top navigation bar
 *
 * Displays the logged-in user's name and action buttons (logout, view site).
 *
 * WHY usePage() for user data?
 *  - HandleInertiaRequests middleware shares `auth.user` on every response.
 *    usePage().props gives reactive access to all shared props without prop drilling.
 *    This means we never need to pass `user` down from a parent component.
 *
 * WHY router.post() for logout instead of a plain <a href="/logout">?
 *  - Laravel's logout route is a POST (CSRF-protected). A GET link would fail
 *    with a 405 Method Not Allowed. Inertia's router.post() sends the CSRF token
 *    automatically via the axios defaults set in bootstrap.js.
 */
import { usePage, router } from '@inertiajs/vue3';

// Read the authenticated user from Inertia's shared props.
// This is NOT reactive to changes within the same page — it's set on each navigation.
const user = usePage().props.auth.user;

// POST to Laravel's logout route — Inertia sends the CSRF token header automatically
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <header class="bg-white dark:bg-gray-800 shadow px-6 py-4 flex justify-between items-center">
        <!-- Welcome message — shows the authenticated user's display name -->
        <h1 class="font-semibold text-gray-800 dark:text-white">Welcome, {{ user.name }}</h1>

        <div class="flex items-center gap-4">
            <!--
                "View Website" button — navigates to the public storefront.
                pi pi-external-link icon signals "opens a different section".
                router.get('/') is an Inertia visit — no full page reload.
            -->
            <button
                class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                @click="router.get(route('home'))"
            >
                <i class="pi pi-external-link" />
                Website
            </button>

            <!--
                Logout button — triggers a POST via Inertia (required for CSRF).
                pi pi-sign-out: door-with-arrow icon, universally recognised for logout.
            -->
            <button
                class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                @click="logout"
            >
                <i class="pi pi-sign-out" />
                Logout
            </button>
        </div>
    </header>
</template>
