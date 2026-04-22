<script setup>
/**
 * AuthenticatedLayout.vue — Admin shell layout
 *
 * Wraps every admin page with: Sidebar + Topbar + main content area.
 * Also mounts the global Toast and ConfirmDialog overlays.
 *
 * WHY mount Toast and ConfirmDialog here (not in app.js)?
 *  - They are DOM elements that need to sit above all page content in the z-index stack.
 *    Mounting them once in the layout means every admin page gets them automatically
 *    without each page needing to include <Toast /> and <ConfirmDialog /> individually.
 *  - ToastService and ConfirmationService are registered in app.js — that gives us
 *    useToast() and useConfirm() project-wide. The actual <Toast> and <ConfirmDialog>
 *    components just provide the DOM outlet those services render into.
 *
 * WHY watch flash messages here?
 *  - Laravel redirects send flash data (->with('success', '...')). Inertia carries
 *    these as shared props via HandleInertiaRequests. Watching them here means
 *    EVERY admin page shows success/error toasts automatically after a controller
 *    redirect — no per-page toast setup needed.
 */
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import AppSidebar from '@/Components/layout/AppSidebar.vue';
import AppTopbar from '@/Components/layout/AppTopbar.vue';

// PrimeVue overlay components — mounted once, used by every admin page
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// usePage() returns the reactive Inertia page object.
// page.props contains all shared props from HandleInertiaRequests + per-page props.
const page = usePage();

// useToast() returns the service instance registered by ToastService in app.js.
// Calling toast.add() pushes a message into the <Toast /> component rendered below.
const toast = useToast();

// Watch for flash.success changes after each Inertia navigation.
// `() => page.props.flash?.success` is a getter — Vue tracks the reactive dependency.
// `?.` optional chaining handles the case where flash is undefined on first load.
watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.add({
                severity: 'success', // green colour scheme
                summary: 'Success',
                detail: message,     // e.g. "Category created successfully."
                life: 3000,          // auto-dismiss after 3 seconds
            });
        }
    },
);

// Same pattern for error flash — shown in red
watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: message,
                life: 5000, // errors stay visible longer than successes
            });
        }
    },
);
</script>

<template>
<!--
    Full-height flex container: sidebar takes fixed width, main area fills the rest.
    overflow-hidden on the wrapper prevents double scrollbars — only <main> scrolls.
-->
<div class="layout-wrapper flex h-screen bg-gray-100 dark:bg-gray-900">

    <!-- Fixed-width collapsible sidebar (width managed internally by AppSidebar) -->
    <AppSidebar />

    <!-- Right column: topbar + scrollable page content -->
    <div class="flex flex-col flex-1 overflow-hidden">

        <!--
            Toast: the floating notification overlay. Positioned top-right by default.
            Must be inside the layout so it's above the page content in the DOM.
        -->
        <Toast />

        <!--
            ConfirmDialog: the "Are you sure?" modal triggered by useConfirm().require().
            Rendered here once so every delete button in the admin works out of the box.
        -->
        <ConfirmDialog />

        <!-- Sticky top navigation bar with user name and action buttons -->
        <AppTopbar />

        <!--
            <slot /> renders the page-specific content from each admin page component.
            overflow-y-auto makes this area independently scrollable — the sidebar
            and topbar stay fixed while the content area scrolls.
        -->
        <main class="flex-1 overflow-y-auto p-6">
            <slot />
        </main>

    </div>
</div>
</template>
