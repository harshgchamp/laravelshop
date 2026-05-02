<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import RoleForm from './Partials/RoleForm.vue';

defineProps({
    permissions: Array,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create Role</h1>
        </div>

        <RoleForm
            :role="null"
            :permissions="permissions"
            :submit-url="route('admin.roles.store')"
            method="post"
        />
    </AdminLayout>
</template>
