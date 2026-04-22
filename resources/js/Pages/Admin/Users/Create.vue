<script setup>
 
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue'
import UserForm from './Partials/UserForm.vue' 

defineProps({ 
    roles: Object
})
</script>

<template>
<AdminLayout>
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Create User
        </h1>
    </div>

    <UserForm
        :user="null"
        :roles="roles"
        :submit-url="route('admin.users.store')"
        method="post"
    />

</AdminLayout>
</template>
