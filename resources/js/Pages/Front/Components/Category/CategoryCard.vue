<script setup lang="ts">
defineProps<{
    category: {
        id: number;
        name: string;
        image: string;
        slug: string;
    };
}>();
</script>

<template>
    <Link :href="route('category', category.slug)">
        <div class="relative cursor-pointer">
            <img
                class="mx-auto h-auto w-auto brightness-50 duration-300 hover:brightness-100"
                :src="category.image"
                :alt="category.name"
                :title="category.name"
            />

            <p
                class="pointer-events-none absolute top-1/2 left-1/2 w-11/12 -translate-x-1/2 -translate-y-1/2 text-center text-white lg:text-xl"
            >
                {{ category.name }}
            </p>
        </div>
    </Link>
</template>
