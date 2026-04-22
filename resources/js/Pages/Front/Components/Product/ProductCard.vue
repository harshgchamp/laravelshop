<script setup lang="ts">
defineProps<{
  product: {
    id: number
    title: string
    price: number
    image: string
    slug: string
    discount_price?: number
  }
}>()
</script>

<template>
  <div class="flex flex-col" :title="product.title">
    <div class="relative flex">
      <img :src="product.image" class="w-full" :alt="product.title" :title="product.title" />
      <div
        class="absolute flex h-full w-full items-center justify-center gap-3 opacity-0 duration-150 hover:opacity-100">
        <Link :href="`/product/${product.slug}`">
        <span class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-amber-400">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
        </span>
        </Link>

      </div>

      <div class="absolute right-1 mt-3 flex items-center justify-center bg-amber-400">
        <p class="px-2 py-2 text-sm">&minus; 25&percnt; OFF</p>
      </div>
    </div>

    <div>
      <p class="mt-2">{{ product.title }}</p>
      <p class="font-medium text-violet-900">
        ${{ product.discount_price ? product.discount_price : product.price }}
        <span v-if="product.discount_price" class="text-sm text-gray-500 line-through">${{ product.price }}</span>
      </p>

      <div>
        <Link :href="`/product/${product.slug}`" class="block w-full text-center bg-violet-900 text-white py-2 rounded-md">
          Details
        </Link>
      </div>
    </div>
  </div>
</template>