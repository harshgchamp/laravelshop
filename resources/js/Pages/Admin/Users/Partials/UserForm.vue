<script setup> 

import { watch, ref } from 'vue'
import { useForm } from '@inertiajs/vue3' 

import InputText from 'primevue/inputtext' 
import Button from 'primevue/button' 
import { useToast } from 'primevue/usetoast'
import { Password } from 'primevue'
import Select from 'primevue/select'

const toast = useToast()

const props = defineProps({
    user: {
        type: Object,
        default: null
    },
    roles: Object,
    submitUrl: String,     // route URL
    method: String         // post or put
})
 

// Initialize Form

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: props.roles?.[0]?.name || 'admin',
    methodText: 'Created',
    _method: props.method === 'put' ? 'put' : 'post',
})
 

const preview = ref(null)   

watch(
    () => props.user,
    (user) => {
        if (user) {
            form.name = user.name
            form.email = user.email
            form.role = user.role
            form.methodText = 'Updated'
        }
    },
    { immediate: true }
)
 
 
const submit = () => { 
    form.post(props.submitUrl, {
        forceFormData: true, // required for file upload
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Created', detail: 'User ' + form.methodText + ' successfully', life: 3000 })
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to ' + form.methodText + ' User', life: 3000 })
        }
    })
}

 
</script>

<template>
<div class="card bg-white dark:bg-gray-800 p-6 rounded-lg shadow">

    <!-- Name -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Name
        </label>
        <InputText v-model="form.name" class="w-full" />
        <small v-if="form.errors.name" class="text-red-500">
            {{ form.errors.name }}
        </small>
    </div> 
    
    <!-- Email -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Email
        </label>
        <InputText v-model="form.email" class="w-full" />
        <small v-if="form.errors.email" class="text-red-500">
            {{ form.errors.email }}
        </small>
    </div> 
    
    <!-- Password -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Password
        </label>
        <Password v-model="form.password" class="w-full" />
        <small v-if="form.errors.password" class="text-red-500">
            {{ form.errors.password }}
        </small>
    </div> 
    
    <!-- Password Confirmation -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Password Confirmation
        </label>
        <Password v-model="form.password_confirmation" class="w-full" />
        <small v-if="form.errors.password_confirmation" class="text-red-500">
            {{ form.errors.password_confirmation }}
        </small>
    </div> 
    
    <!-- Role -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Role
        </label>
        <Select v-model="form.role" :options="props.roles" optionValue="code" optionLabel="name" placeholder="Select" />
        <small v-if="form.errors.role" class="text-red-500">
            {{ form.errors.role }}
        </small>
    </div> 
    

    <!-- Submit Button -->
    <Button
        label="Save"
        icon="pi pi-check"
        :loading="form.processing"
        @click="submit"
    />
</div>
</template>
