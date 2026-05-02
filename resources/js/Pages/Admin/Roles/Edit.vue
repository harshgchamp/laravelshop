<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import RoleForm from './Partials/RoleForm.vue';

defineProps({
    role: Object,
    permissions: Array,
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Role</h1>
        </div>

        <RoleForm
            :role="role"
            :permissions="permissions"
            :submit-url="route('admin.roles.update', role.id)"
            method="put"
        />
    </AdminLayout>
</template>
