<script setup lang="ts">
import CategoryCard from '@/Pages/Front/Components/Category/CategoryCard.vue';

defineProps<{
    categories: any[];
}>();
</script>

<template>
    <h2 class="mx-auto mb-5 max-w-[1200px] px-5">SHOP BY CATEGORY</h2>
    <section class="mx-auto grid max-w-[1200px] grid-cols-2 px-5 lg:grid-cols-3 lg:gap-5">
        <CategoryCard v-for="cat in categories" :key="cat.id" :category="cat" />
    </section>
</template>
