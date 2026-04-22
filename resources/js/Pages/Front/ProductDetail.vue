<script setup lang="ts">
import FrontLayout from '@/Pages/Front/Layouts/FrontLayout.vue';
import { router } from '@inertiajs/vue3';

defineProps<{
    product: {
        id: number;
        title: string;
        description: string;
        price: number;
        in_stock: number;
        image: string;
        discount_price: number;
        category: {
            data: {
                name: string;
            };
        };
    };
    relatedProducts: Object;
}>();

const addToCart = (product) => {
    router.post(
        route('cart.store', product),
        {},
        {
            onSuccess: () => {
                alert('Product added to cart');
            },
        },
    );
};
</script>

<template>
    <FrontLayout>
        <!-- breadcrumbs  -->

        <nav class="mx-auto w-full mt-4 max-w-[1200px] px-5">
            <ul class="flex items-center">
                <li class="cursor-pointer">
                    <a href="index.html">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                            <path
                                d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z"
                            />
                            <path
                                d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z"
                            />
                        </svg>
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-500">&gt;</span>
                </li>

                <li class="text-gray-500">{{ product.title }}</li>
            </ul>
        </nav>
        <!-- /breadcrumbs  -->

        <section
            class="container flex-grow mx-auto max-w-[1200px] border-b py-5 lg:grid lg:grid-cols-2 lg:py-10"
        >
            <!-- product image -->

            <div class="container mx-auto px-4" :title="product.title">
                <img
                    class="w-full"
                    :src="product.image"
                    :alt="product.title"
                    :title="product.title"
                />
            </div>

            <!-- description  -->

            <div class="mx-auto px-5 lg:px-5">
                <h2 class="pt-3 text-2xl font-bold lg:pt-0">{{ product.title }}</h2>

                <p class="mt-5 font-bold">
                    Availability:
                    <span class="text-green-600">{{
                        product.in_stock > 0 ? 'In Stock' : 'Out of Stock'
                    }}</span>
                </p>

                <p class="font-bold">
                    Category: <span class="font-normal">{{ product.category?.data?.name }}</span>
                </p>

                <p class="mt-4 text-4xl font-bold text-violet-900">
                    ${{ product.discount_price ? product.discount_price : product.price }}
                    <span v-if="product.discount_price" class="text-xs text-gray-400 line-through"
                        >${{ product.price }}</span
                    >
                </p>

                <p
                    class="pt-5 text-sm leading-5 text-gray-500 text-justify"
                    v-html="product.description"
                ></p>

                <div v-if="product.in_stock > 0" class="mt-7 flex flex-row items-center gap-6">
                    <button
                        :disabled="product.in_stock <= 0"
                        class="flex h-12 w-1/3 items-center justify-center bg-violet-900 text-white duration-100 hover:bg-blue-800"
                        @click="addToCart(product)"
                    >
                        Add to cart
                    </button>

                    <button
                        class="flex h-12 w-1/3 items-center justify-center bg-amber-400 duration-100 hover:bg-yellow-300"
                    >
                        Wishlist
                    </button>
                </div>
            </div>
        </section>

        <!-- product details  -->

        <section class="container mx-auto max-w-[1200px] px-5 py-5 lg:py-10">
            <h2 class="text-xl">Product details</h2>
            <p class="mt-4 lg:w-3/4 text-justify" v-html="product.description"></p>
        </section>
        <!-- /product details  -->
    </FrontLayout>
</template>
