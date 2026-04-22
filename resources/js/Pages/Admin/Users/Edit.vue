<script setup>
/**
 * Admin/Users/Edit.vue — Edit an existing user
 * Props from UserController@edit: user (UserResource), roles (all Spatie roles).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import UserForm from './Partials/UserForm.vue';

defineProps({
    user: Object, // UserResource: { id, name, email, role, created_at }
    roles: Object, // [{ id, name, guard_name }]
});
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit User</h1>
        </div>

        <UserForm
            :user="user"
            :roles="roles"
            :submit-url="route('admin.users.update', user.id)"
            method="put"
        />
    </AdminLayout>
</template>
