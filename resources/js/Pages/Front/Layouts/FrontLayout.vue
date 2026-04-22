<script setup lang="ts">
import Header from '@/Pages/Front/Components/Header/Header.vue';
import Footer from '@/Pages/Front/Components/Footer/Footer.vue';
</script>

<template>
    <div>
        <Header />

        <main>
            <slot />
        </main>

        <Footer />
    </div>
</template>
