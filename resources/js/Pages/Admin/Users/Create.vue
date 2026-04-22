<script setup>
/**
 * Admin/Users/Create.vue — Create a new user
 * Props from UserController@create: roles (all available Spatie roles).
 */
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import UserForm    from './Partials/UserForm.vue';

defineProps({
    roles: Object, // [{ id, name, guard_name }]
});
</script>

<template>
<AdminLayout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Create User</h1>
    </div>

    <UserForm
        :user="null"
        :roles="roles"
        :submit-url="route('admin.users.store')"
        method="post"
    />
</AdminLayout>
</template>
