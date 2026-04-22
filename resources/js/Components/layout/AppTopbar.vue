<script setup> 
import { usePage,router } from '@inertiajs/vue3'
 

const user = usePage().props.auth.user

const logout = () => { 
    router.post('/logout')
}
</script>

<template>
    <header class="bg-white dark:bg-gray-800 shadow px-6 py-4 flex justify-between">

        <h1 class="font-semibold text-gray-800 dark:text-white">
            Welcome, {{ user.name }}
        </h1>

        <div class="flex items-center gap-4"> 

            <button @click="logout" class="pi pi-sign-out">Logout</button>
            
            <button @click="router.get('/')" class="pi pi-sign-out">Website</button>

        </div>

        

    </header>
</template>
