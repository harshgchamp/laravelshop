<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const collapsed = ref(false)

const toggle = () => collapsed.value = !collapsed.value
</script>

<template>
<div :class="[
    'bg-white dark:bg-gray-800 shadow-lg transition-all duration-300',
    collapsed ? 'w-20' : 'w-64'
]">

    <div class="p-4 flex justify-between items-center">
        <span class="font-bold text-lg text-gray-800 dark:text-white">
            Admin
        </span>
        <button @click="toggle" class="pi pi-bars"></button>
    </div>

    <nav class="mt-4">

        <Link href="/dashboard"
            class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="pi pi-home mr-2"></i> Dashboard
        </Link>

        <Link href="/admin/categories"
            class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="pi pi-tags mr-2"></i> Categories
        </Link>

        <Link href="/admin/users"
            class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="pi pi-users mr-2"></i> Users
        </Link>

        <Link href="/admin/permissions"
            class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="pi pi-key mr-2"></i> Permissions
        </Link>

        <Link href="/admin/products"
            class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="pi pi-box mr-2"></i> Products
        </Link>

    </nav>
</div>
</template>
