<script setup lang="ts">
import FrontLayout from '@/Pages/Front/Layouts/FrontLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { upperCase } from 'lodash';

const page = usePage();
const user = page.props.user;

defineProps<{
    orders: {
        data: any[];
    };
}>();
</script>

<template>
    <FrontLayout>
        <nav class="mx-auto w-full mt-4 max-w-[1200px] px-5">
            <ul class="flex items-center">
                <li class="cursor-pointer">
                    <Link :href="route('home')">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"
                            />
                            <path
                                d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"
                            />
                        </svg>
                    </Link>
                </li>
                <li>
                    <span class="mx-2 text-gray-500">&gt;</span>
                </li>

                <li class="text-gray-500">My Order History</li>
            </ul>
        </nav>

        <section
            class="container flex-grow mx-auto max-w-[1200px] border-b py-5 lg:flex lg:flex-row lg:py-10"
        >
            <!-- sidebar  -->
            <section class="hidden w-[300px] flex-shrink-0 px-4 lg:block">
                <div class="border-b py-5">
                    <div class="flex items-center">
                        <img
                            width="40px"
                            height="40px"
                            class="rounded-full object-cover"
                            src="/images/avatar-photo.png"
                            alt="Red woman portrait"
                        />
                        <div class="ml-5">
                            <p class="font-medium text-gray-500">Hello,</p>
                            <p class="font-bold">{{ upperCase(user.name) }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex border-b py-5">
                    <div class="flex w-full">
                        <div class="flex flex-col gap-2">
                            <Link
                                :href="route('account.orders.index')"
                                class="flex items-center gap-2 font-medium text-violet-900"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>

                                My Order History</Link
                            >
                        </div>
                    </div>
                </div>
            </section>
            <!-- /sidebar  -->

            <!-- Order table  -->

            <section
                class="hidden h-[300px] w-full max-w-[1200px] grid-cols-1 gap-3 px-5 pb-10 lg:grid"
            >
                <table class="table-fixed">
                    <thead class="h-16 bg-neutral-100">
                        <tr>
                            <th>ORDER</th>
                            <th>DATE</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="border-b">
                            <td class="mx-auto text-center">
                                <span class="border-2 border-green-500 py-1 px-3 text-green-500">{{
                                    order.id
                                }}</span>
                            </td>
                            <td class="mx-auto text-center">
                                {{ new Date(order.created_at).toLocaleDateString() }}
                            </td>

                            <td class="mx-auto text-center">{{ order.total_price }}</td>

                            <td class="mx-auto text-center">{{ order.status }}</td>

                            <td class="text-center align-middle">
                                <Link
                                    :href="`/account/orders/${order.id}`"
                                    class="bg-amber-400 px-4 py-2"
                                    ><button class="text-center">View</button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
            <!-- /Order table  -->
        </section>
    </FrontLayout>
</template>
