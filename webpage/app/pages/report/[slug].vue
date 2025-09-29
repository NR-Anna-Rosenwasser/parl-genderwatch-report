<script setup>
const slug = useRoute().params.slug
const { data: report } = await useAsyncData(`report-${slug}`, () => {
  return queryCollection('report').path(`/report/${slug}`).first()
})
if (!report.value) {
  throw createError({ statusCode: 404, statusMessage: 'Report not found' })
}
</script>

<template>
    {{ report?.title }}
    {{ report?.lead }}
</template>