<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

// auth is shared via Inertia's HandleInertiaRequests middleware — always available globally
const auth = usePage().props.auth;
</script>

<template>
    <!-- Nav bar -->
    <!-- hidden on small devices -->

    <nav class="relative bg-violet-900">
        <div class="mx-auto hidden h-12 w-full max-w-[1200px] items-center md:flex">
            <div class="mx-7 flex gap-8">
                <Link
                    :href="route('home')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                >
                    Home
                </Link>

                <Link
                    :href="route('category')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                >
                    Catalog
                </Link>

                <Link
                    :href="route('home')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                >
                    About Us
                </Link>

                <Link
                    :href="route('home')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                >
                    Contact Us
                </Link>
            </div>

            <div v-if="!auth.user" class="ml-auto flex gap-4 px-5">
                <Link
                    :href="route('login')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                    >Login</Link
                >

                <span class="text-white">&#124;</span>

                <Link
                    :href="route('register')"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                    >Sign Up</Link
                >
            </div>

            <div v-else>
                <Link
                    :href="route('logout')"
                    method="post"
                    class="font-light text-white duration-100 hover:text-yellow-400 hover:underline"
                >
                    Logout
                </Link>
            </div>
        </div>
    </nav>
    <!-- /Nav bar -->
</template>
