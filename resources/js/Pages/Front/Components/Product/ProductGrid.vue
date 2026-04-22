<script setup lang="ts">
import ProductCard from '@/Pages/Front/Components/Product/ProductCard.vue'

defineProps<{
  products: any[]
}>()
 
</script>

<template>
    
  
    <ProductCard
      v-for="product in products"
      :key="product.id"
      :product="product"
    /> 
</template>