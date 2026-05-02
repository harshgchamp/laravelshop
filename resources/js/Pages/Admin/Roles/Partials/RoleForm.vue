<script setup>
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';

const toast = useToast();

const props = defineProps({
    role: { type: Object, default: null },
    permissions: { type: Array, required: true },
    submitUrl: { type: String, required: true },
    method: { type: String, required: true },
});

const form = useForm({
    name: '',
    permissions: [],
    _method: props.method === 'put' ? 'put' : 'post',
});

const methodText = computed(() => (props.method === 'put' ? 'Updated' : 'Created'));

// Prefill on edit — role.permissions is an array of permission IDs from RoleResource
watch(
    () => props.role,
    (role) => {
        if (role) {
            form.name = role.name;
            form.permissions = role.permissions ?? [];
        }
    },
    { immediate: true },
);

const submit = () => {
    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: methodText.value,
                detail: `Role ${methodText.value.toLowerCase()} successfully.`,
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Please check the form fields and try again.',
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <div class="card bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <!-- Role name -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
                Role Name
            </label>
            <InputText v-model="form.name" class="w-full" placeholder="e.g. editor, moderator" />
            <small v-if="form.errors.name" class="text-red-500 mt-1 block">
                {{ form.errors.name }}
            </small>
        </div>

        <!-- Permission assignment — multi-select over all permissions -->
        <div class="mb-6">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
                Permissions
            </label>
            <!--
                option-value="id"  → form.permissions stores an array of permission IDs.
                option-label="name" → visible text for each option.
                filter="true"       → search box inside the dropdown for large permission sets.
            -->
            <MultiSelect
                v-model="form.permissions"
                :options="permissions"
                option-value="id"
                option-label="name"
                placeholder="Assign permissions…"
                :filter="true"
                :show-clear="true"
                class="w-full"
                display="chip"
            />
            <small v-if="form.errors.permissions" class="text-red-500 mt-1 block">
                {{ form.errors.permissions }}
            </small>
        </div>

        <Button label="Save Role" icon="pi pi-check" :loading="form.processing" @click="submit" />
    </div>
</template>
