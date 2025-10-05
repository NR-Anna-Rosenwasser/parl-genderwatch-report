<script setup>

const reports = await queryCollection('reports').all()
console.log(reports)
</script>

<template>
    <div class="gwr-report-grid">
        <div v-for="report in reports" :key="report.number" class="gwr-report-card">
            <div class="gwr-report-card p-4 border border-accent">
                <NuxtLink :to="`/reports/${report.number}`">
                    <h2 class="text-xl md:text-2xl underline noline font-inter font-extrabold mb-1 mt-0 pt-0">{{ report.title }}</h2>
                </NuxtLink>
                <p class="text-sm italic text-accent">{{ new Date(report.date).toLocaleDateString('de-CH', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }) }}</p>
                <p class="text-gray-800 mb-4" v-html="report.teaser"></p>
                <NuxtLink :to="`/reports/${report.number}`" class="no-underline">
                    <span class="flex items-center gap-2"><Icon name="mdi:chevron-right-circle" class="text-accent text-xl" /> <em>Bericht ansehen</em></span>
                </NuxtLink>
            </div>
        </div>
    </div>
</template>