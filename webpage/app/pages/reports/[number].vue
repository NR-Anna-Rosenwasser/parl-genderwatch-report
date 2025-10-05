<script setup>
const number = useRoute().params.number
const report = await queryCollection('reports').where('number', '=', Number(number)).first()
console.log(report)
</script>

<template>
    <div class="gwr-default-post mt-4">
        <p class="text-sm mb-2 italic text-accent">{{ new Date(report.date).toLocaleDateString('de-CH', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }) }}</p>
        <h1 class="mt-4">{{ report.title }}</h1>
        <p class="text-gray-800 mb-4 text-xl font-bold" v-html="report.teaser"></p>
        <ContentRenderer :value="report" />
        <Spacer size="xl" />
        <Footer />
    </div>
</template>