<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
const props = defineProps({
    users: Number, 
    categories: Number,
    products: Number,
});
</script>

<template>

    <AdminLayout> 

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Dashboard
            </h1>
        </div>
        
        <div class="grid grid-cols-12 gap-8 ">
            <div class="col-span-12 lg:col-span-6 xl:col-span-4 ">
                <div class="card mb-0 bg-white custom-card">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">Users</span>
                            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ props.users }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border"
                            style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-users text-blue-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="card mb-0 bg-white custom-card">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">Categories</span>
                            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ props.categories }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border"
                            style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-unlock text-orange-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-6 xl:col-span-4">
                <div class="card mb-0 bg-white custom-card" >
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">Products</span>
                            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ props.products
                                }}</div>
                        </div>
                        <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border"
                            style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-key text-cyan-500 !text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-card {
    border-radius: 10px;
    padding: 10px;
}
</style>