<script setup>
/**
 * UserForm.vue — Shared create/edit form for the User resource
 *
 * Handles: name, email, password (+ confirmation), and role assignment.
 * Password fields are optional on edit — the server only updates password
 * if a non-empty value is submitted (handled in UserUpdateRequest).
 */
import { watch, computed } from 'vue'; // `ref` not needed — no image upload in user form
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import InputText from 'primevue/inputtext';
import Button    from 'primevue/button';
import Select    from 'primevue/select';
// Password: PrimeVue input with show/hide toggle and optional strength meter
import { Password } from 'primevue';

const toast = useToast();

const props = defineProps({
    user:      { type: Object, default: null }, // null on Create, UserResource on Edit
    roles:     { type: Object, required: true }, // Spatie roles: [{ id, name, guard_name }]
    submitUrl: { type: String, required: true },
    method:    { type: String, required: true }, // 'post' | 'put'
});

// ─── Inertia Form ────────────────────────────────────────────────────────────
const form = useForm({
    name:                  '',
    email:                 '',
    password:              '',
    password_confirmation: '',
    // Default to first available role name, or 'user' as fallback.
    // Spatie roles have a `name` field (e.g. 'admin', 'user') — NOT a `code` field.
    role:                  props.roles?.[0]?.name ?? 'user',
    _method:               props.method === 'put' ? 'put' : 'post',
});

// UI-only label — not sent to server
const methodText = computed(() => props.method === 'put' ? 'Updated' : 'Created');

// ─── Prefill on Edit ─────────────────────────────────────────────────────────
watch(
    () => props.user,
    (user) => {
        if (user) {
            form.name  = user.name;
            form.email = user.email;
            form.role  = user.role; // role name string from UserResource
            // Password intentionally NOT prefilled — user must re-enter to change
        }
    },
    { immediate: true },
);

// ─── Submit ──────────────────────────────────────────────────────────────────
const submit = () => {
    form.post(props.submitUrl, {
        // forceFormData: true not strictly needed (no file upload) but keeps
        // behaviour consistent with CategoryForm and ProductForm
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: methodText.value,
                detail: `User ${methodText.value.toLowerCase()} successfully.`,
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

    <!-- Name -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Name</label>
        <InputText v-model="form.name" class="w-full" placeholder="Full name" />
        <small v-if="form.errors.name" class="text-red-500 mt-1 block">{{ form.errors.name }}</small>
    </div>

    <!-- Email — must be unique in the users table (enforced in UserStoreRequest/UserUpdateRequest) -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Email</label>
        <InputText v-model="form.email" type="email" class="w-full" placeholder="user@example.com" />
        <small v-if="form.errors.email" class="text-red-500 mt-1 block">{{ form.errors.email }}</small>
    </div>

    <!--
        Password — PrimeVue's Password component adds a show/hide toggle icon.
        :feedback="false" disables the strength meter popup (keeps UI clean).
        On Edit: leave blank to keep the existing password unchanged.
    -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
            Password
            <span v-if="props.method === 'put'" class="text-gray-400 text-sm font-normal">
                (leave blank to keep current)
            </span>
        </label>
        <Password v-model="form.password" class="w-full" :feedback="false" toggleMask />
        <small v-if="form.errors.password" class="text-red-500 mt-1 block">{{ form.errors.password }}</small>
    </div>

    <!-- Password confirmation — must match `password` field (validated server-side) -->
    <div class="mb-4">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Confirm Password</label>
        <Password v-model="form.password_confirmation" class="w-full" :feedback="false" toggleMask />
        <small v-if="form.errors.password_confirmation" class="text-red-500 mt-1 block">
            {{ form.errors.password_confirmation }}
        </small>
    </div>

    <!--
        Role selector using Spatie roles.
        optionValue="name" → form.role stores the role name string (e.g. 'admin').
                             Spatie roles do NOT have a `code` field — use `name`.
        optionLabel="name" → the visible text in the dropdown.
        The server assigns the role via $user->syncRoles([$request->role]).
    -->
    <div class="mb-6">
        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Role</label>
        <Select
            v-model="form.role"
            :options="roles"
            optionValue="name"
            optionLabel="name"
            placeholder="Select a role"
        />
        <small v-if="form.errors.role" class="text-red-500 mt-1 block">{{ form.errors.role }}</small>
    </div>

    <Button
        label="Save User"
        icon="pi pi-check"
        :loading="form.processing"
        @click="submit"
    />
</div>
</template>
