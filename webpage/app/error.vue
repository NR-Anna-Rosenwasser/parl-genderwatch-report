<script setup lang="ts">
import type { NuxtError } from 'nuxt/app'

const props = defineProps({
  error: Object as () => NuxtError,
})
const error = props.error!
</script>

<template>
  <div>
    <h1>{{ error.statusCode }}</h1>
    <NuxtLink to="/">Go back home</NuxtLink>
  </div>
</template>