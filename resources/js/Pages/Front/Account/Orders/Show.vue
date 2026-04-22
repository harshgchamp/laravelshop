<script setup lang="ts">
import FrontLayout from '@/Pages/Front/Layouts/FrontLayout.vue';

defineProps<{
    order: any[];
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

            <section class="w-full max-w-[1200px] gap-3 px-5 pb-10">
                <table class="hidden w-full md:table">
                    <thead class="h-16 bg-neutral-100">
                        <tr>
                            <th>Title</th>
                            <th>PRICE</th>
                            <th>QUANTITY</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 1 -->

                        <tr v-for="item in order.items" :key="item.id" class="h-[100px] border-b">
                            <td class="align-middle">
                                {{ item.product.title }}
                            </td>
                            <td class="mx-auto text-center">&#36;{{ item.unit_price }}</td>
                            <td class="text-center align-middle">{{ item.quantity }}</td>
                            <td class="mx-auto text-center">
                                &#36;{{ (item.quantity * item.unit_price).toFixed(2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- /Product table  -->

                <!-- Summary  -->

                <section class="my-5 flex w-full flex-col gap-4 lg:flex-row">
                    <div class="lg:w-1/2">
                        <div class="border py-5 px-4 shadow-md">
                            <p class="font-bold">ORDER SUMMARY</p>

                            <div class="flex justify-between border-b py-5">
                                <p>Subtotal</p>
                                <p>${{ order.total_price }}</p>
                            </div>

                            <div class="flex justify-between border-b py-5">
                                <p>Shipping</p>
                                <p>Free</p>
                            </div>

                            <div class="flex justify-between py-5">
                                <p>Total</p>
                                <p>${{ order.total_price }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address info  -->

                    <div class="lg:w-1/2">
                        <div class="border py-5 px-4 shadow-md">
                            <p class="font-bold">ORDER INFORMATION</p>

                            <div>
                                <p>Order &num;{{ order.id }}</p>
                            </div>

                            <div class="flex flex-col border-b py-5">
                                <p>
                                    Status:
                                    <span class="font-bold text-green-600">{{ order.status }}</span>
                                </p>

                                <p>Date: {{ new Date(order.created_at).toLocaleDateString() }}</p>
                            </div>

                            <div></div>

                            <div class="flex flex-col border-b py-5">
                                <p class="font-bold">ADDRESS INFORMATION</p>
                                <p>Country: {{ order.payment_address.country_code }}</p>
                                <p>City: {{ order.payment_address.city }}</p>
                                <p>PostCode: {{ order.payment_address.postcode }}</p>
                            </div>

                            <div class="flex flex-col py-5">
                                <p class="font-bold">PAYMENT INFORMATION</p>
                                <p>Payment method: {{ order.payment_details.type }}</p>
                                <p>Payment Status: {{ order.payment_details.status }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- /Address info  -->
                </section>
            </section>

            <!-- /Order table  -->
        </section>
    </FrontLayout>
</template>
