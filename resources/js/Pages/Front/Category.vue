<script setup lang="ts">
import FrontLayout from '@/Pages/Front/Layouts/FrontLayout.vue';

import ProductGrid from '@/Pages/Front/Components/Product/ProductGrid.vue';

import Filter from '@/Pages/Front/Components/Product/Filters.vue';

const props = defineProps<{
    filters: Object;
    category: {
        id: number;
        name: string;
        slug: string;
    };
    products: {
        data: any[];
    };
}>();
</script>

<template>
    <FrontLayout>
        <!-- breadcrumbs  -->

        <nav class="mx-auto w-full mt-4 max-w-[1200px] px-5">
            <ul class="flex items-center">
                <li class="cursor-pointer">
                    <a href="index.html">
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
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-500">&gt;</span>
                </li>

                <li class="text-gray-500">{{ category?.name ?? 'All Products' }}</li>
            </ul>
        </nav>
        <!-- /breadcrumbs  -->

        <section
            class="container mx-auto flex-grow max-w-[1200px] border-b py-5 lg:flex lg:flex-row lg:py-10"
        >
            <!-- sidebar  -->
            <Filter :filters="props.filters" :category="props.category" />
            <!-- /sidebar  -->

            <div>
                <section
                    class="mx-auto grid max-w-[1200px] grid-cols-2 gap-3 px-5 pb-10 lg:grid-cols-3"
                >
                    <ProductGrid :products="props.products.data" />
                </section>
            </div>
        </section>
    </FrontLayout>
</template>
