<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import NavMenu from '@/Pages/Front/Components/Header/NavMenu.vue';
</script>

<template>
    <!-- Header -->
    <header class="mx-auto flex h-16 max-w-[1200px] items-center justify-between px-5">
        <Link :href="route('home')">
            <img
                class="cursor-pointer sm:h-auto sm:w-auto"
                src="/images/company-logo.svg"
                alt="company logo"
            />
        </Link>

        <div class="md:hidden">
            <button>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-8 w-8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                    />
                </svg>
            </button>
        </div>

        <div class="hidden gap-3 md:!flex">
            <Link
                :href="route('cart.view')"
                class="flex cursor-pointer flex-col items-center justify-center"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="h-6 w-6"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 004.25 22.5h15.5a1.875 1.875 0 001.865-2.071l-1.263-12a1.875 1.875 0 00-1.865-1.679H16.5V6a4.5 4.5 0 10-9 0zM12 3a3 3 0 00-3 3v.75h6V6a3 3 0 00-3-3zm-3 8.25a3 3 0 106 0v-.75a.75.75 0 011.5 0v.75a4.5 4.5 0 11-9 0v-.75a.75.75 0 011.5 0v.75z"
                        clip-rule="evenodd"
                    />
                </svg>

                <p class="text-xs">Cart</p>
            </Link>

            <Link
                :href="route('account.orders.index')"
                class="relative flex cursor-pointer flex-col items-center justify-center"
            >
                <span class="absolute bottom-[33px] right-1 flex h-2 w-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                    ></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                </span>
                <span class="absolute bottom-[33px] right-1 flex h-2 w-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                    ></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                </span>

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                    />
                </svg>

                <p class="text-xs">Account</p>
            </Link>
        </div>
    </header>
    <!-- /Header -->

    <!-- Nav -->
    <NavMenu />
</template>
