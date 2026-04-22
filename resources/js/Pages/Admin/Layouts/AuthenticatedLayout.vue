<script setup>
import { computed, ref, watch  } from 'vue';
import AppSidebar from '@/Components/layout/AppSidebar.vue'
import AppTopbar from '@/Components/layout/AppTopbar.vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog' 
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const toast = useToast()

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: message,
                life: 3000
            })
        }
    }
)

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: message,
                life: 3000
            })
        }
    }
)
</script>

<template>
<div class="layout-wrapper flex h-screen bg-gray-100 dark:bg-gray-900">
 

    <!-- Sidebar -->
    <AppSidebar />

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">

        <Toast />
        <ConfirmDialog />
        
        <AppTopbar />

        <main class="flex-1 overflow-y-auto p-6">
            <slot />
        </main>

    </div>

</div>
</template>
