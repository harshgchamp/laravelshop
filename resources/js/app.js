// app.js — Application bootstrap
// This is the single JS entry point that Vite bundles.
// It sets up Vue, Inertia, PrimeVue, Pinia, and Ziggy before mounting the app.

// ─── Styles ──────────────────────────────────────────────────────────────────
// Importing CSS here bundles them into the same output chunk as the JS.
// Both stylesheets are loaded on every page (admin + storefront) because Vite
// has a single entry point. If performance becomes critical, split into two
// entry points: one for admin, one for front — and load separately in Blade.
import '../css/app.css';   // PrimeVue Aura theme overrides + Tailwind base
import '../css/front.css'; // Storefront-specific styles (carousel, hero, etc.)

// ─── Axios Bootstrap ─────────────────────────────────────────────────────────
// Sets axios defaults (CSRF header) so Inertia's internal XHR calls work with Laravel.
import './bootstrap.js';

// ─── Vue Core ────────────────────────────────────────────────────────────────
import { createApp, h } from 'vue';

// ─── Inertia ─────────────────────────────────────────────────────────────────
// createInertiaApp: wires Inertia into Vue — intercepts <Link> clicks, handles
//   browser history, and swaps page components without full page reloads (SPA feel).
// Link: Inertia's <a> replacement — sends XHR instead of full navigation.
// Head: Inertia's <head> manager — lets each page set its own <title> and meta tags.
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';

// resolvePageComponent: given a page name like "Admin/Categories/Index",
// returns the matching Vue component from Pages/ using Vite's import.meta.glob.
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// ─── Ziggy ───────────────────────────────────────────────────────────────────
// ZiggyVue: makes Laravel named routes available in Vue via route('admin.categories.index').
// Reads the Ziggy JS object injected by HandleInertiaRequests middleware (shared props).
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// ─── State Management ────────────────────────────────────────────────────────
// Pinia: Vue 3's official state management library — replaces Vuex.
// createPinia() creates the store instance; individual stores are defined separately.
import { createPinia } from 'pinia';

// ─── PrimeVue UI Library ─────────────────────────────────────────────────────
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';          // Aura: PrimeVue's default design token set
import ToastService from 'primevue/toastservice';  // Enables useToast() composable project-wide
import ConfirmationService from 'primevue/confirmationservice'; // Enables useConfirm() project-wide

// ─── App Name ────────────────────────────────────────────────────────────────
// Read from .env via Vite's import.meta.env — only VITE_ prefixed vars are exposed.
const appName = import.meta.env.VITE_APP_NAME || 'Laravel Shop';

// ─── Inertia App Setup ───────────────────────────────────────────────────────
createInertiaApp({
    // Page title format: "Dashboard - Laravel Shop"
    // The `title` part comes from each page's <Head title="Dashboard" /> component.
    title: (title) => `${title} - ${appName}`,

    // resolve: given a page name string (e.g. "Admin/Categories/Index"), returns
    // the matching .vue file from Pages/. import.meta.glob pre-loads all page
    // components as lazy chunks — Vite code-splits each page automatically.
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    // setup: called once when Inertia mounts. Receives:
    //  el     → the DOM element to mount onto (the <div id="app"> in app.blade.php)
    //  App    → Inertia's root component that renders the current page component
    //  props  → server-side shared props (auth, flash, ziggy, etc.)
    //  plugin → Inertia's Vue plugin (handles page transitions and history)
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)              // Inertia plugin — intercepts Link clicks, XHR navigation
            .use(createPinia())       // Pinia — must be installed before any store is used
            .use(ZiggyVue)            // Ziggy — registers route() globally (window + globalProperties)
            // Register Link and Head as global components so every .vue file can use
            // <Link href="..."> and <Head title="..."> without importing them individually.
            .component('Link', Link)
            .component('Head', Head)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,     // Aura design system — tokens for colours, spacing, radius
                },
            })
            .use(ToastService)        // Required to call useToast() in any component
            .use(ConfirmationService) // Required to call useConfirm() in any component
            .mount(el);
    },

    // progress: thin loading bar shown at the top during Inertia page transitions.
    progress: {
        color: '#4f46e5', // indigo-600 — matches the admin theme accent colour
    },
});
