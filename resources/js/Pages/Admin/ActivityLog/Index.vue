<script setup>
import AdminLayout from '@/Pages/Admin/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

defineProps({
    logs: Object,
});

// ── Event badge styling ───────────────────────────────────────────────────────
// Maps the machine-readable event name to a human label + PrimeVue Tag severity.
const EVENT_META = {
    'user.created_by_admin': { label: 'User Created',  severity: 'success' },
    'role.assigned_to_user': { label: 'Role Changed',  severity: 'warn'    },
};

const eventMeta = (eventName) =>
    EVENT_META[eventName] ?? { label: eventName, severity: 'secondary' };

// ── Properties display ────────────────────────────────────────────────────────
// Format the JSON properties bag into a readable single-line string.
// e.g. { name: "John", role: "editor" } → "name: John · role: editor"
const formatProperties = (props) => {
    if (!props || typeof props !== 'object') return '—';
    return Object.entries(props)
        .map(([k, v]) => `${k}: ${v ?? '—'}`)
        .join(' · ');
};
</script>

<template>
    <AdminLayout>
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Activity Log</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Read-only audit trail of admin actions
                </p>
            </div>

            <DataTable :value="logs.data" striped-rows>

                <!-- Row number -->
                <Column header="#">
                    <template #body="slotProps">
                        {{ (logs.current_page - 1) * logs.per_page + slotProps.index + 1 }}
                    </template>
                </Column>

                <!-- Event badge -->
                <Column header="Event" style="min-width: 160px">
                    <template #body="slotProps">
                        <Tag
                            :value="eventMeta(slotProps.data.event).label"
                            :severity="eventMeta(slotProps.data.event).severity"
                        />
                    </template>
                </Column>

                <!-- Who performed the action -->
                <Column header="Performed By" style="min-width: 160px">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.user" class="font-medium text-gray-800 dark:text-gray-200">
                            {{ slotProps.data.user.name }}
                            <span class="text-xs text-gray-400 ml-1">{{ slotProps.data.user.email }}</span>
                        </span>
                        <span v-else class="text-gray-400 text-sm">deleted user</span>
                    </template>
                </Column>

                <!-- Affected subject -->
                <Column header="Subject" style="min-width: 140px">
                    <template #body="slotProps">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ slotProps.data.subject_type?.split('\\').pop() }}
                            #{{ slotProps.data.subject_id }}
                        </span>
                    </template>
                </Column>

                <!-- JSON properties as readable key: value list -->
                <Column header="Details" style="min-width: 260px">
                    <template #body="slotProps">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ formatProperties(slotProps.data.properties) }}
                        </span>
                    </template>
                </Column>

                <!-- IP address -->
                <Column header="IP" style="min-width: 110px">
                    <template #body="slotProps">
                        <span class="text-xs font-mono text-gray-400">
                            {{ slotProps.data.ip_address ?? '—' }}
                        </span>
                    </template>
                </Column>

                <!-- Timestamp -->
                <Column field="created_at" header="When" style="min-width: 160px" />

            </DataTable>

            <!-- ── Pagination ──────────────────────────────────────────────── -->
            <div v-if="logs.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    {{ (logs.current_page - 1) * logs.per_page + 1 }}–{{
                        Math.min(logs.current_page * logs.per_page, logs.total)
                    }}
                    of {{ logs.total }}
                </p>

                <div class="flex gap-1">
                    <button
                        v-for="link in logs.links"
                        :key="link.label"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-1 text-sm rounded border transition-colors',
                            link.active
                                ? 'bg-primary-500 text-white border-primary-500'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600',
                            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                        ]"
                        @click="link.url && router.get(link.url, {}, { preserveScroll: true })"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
