<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import UserForm from './Partials/UserForm.vue' 

defineProps({
    user: Object,
    roles: Object
})
</script>

<template>
<AdminLayout>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Edit User
        </h1>
    </div>

    <UserForm
        :user="user"
        :submit-url="route('admin.users.update', user.id)"
        method="put"
        :roles="roles"
    />

</AdminLayout>
</template>
