<script setup>
const { data: posts } = await useAsyncData('report', () => queryCollection('report').all())
</script>

<template>
    <div>
        Reports Page
    </div>

    <div>
        <ul>
            <li v-for="post in posts" :key="post.id">
                <NuxtLink :to="post.path">{{ post.title }}</NuxtLink>
            </li>
        </ul>
    </div>
</template>