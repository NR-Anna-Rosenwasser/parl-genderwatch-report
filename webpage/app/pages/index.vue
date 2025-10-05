<script setup>
import {ref} from 'vue'
useSeoMeta({
  title: 'Genderwatch Report – Weil Repräsentation wichtig ist',
  description: 'In der letzen Session des eidgenössischen Parlaments haben Männer 67% der Redezeit eingenommen. Der Genderwatch Report zeigt, wie es um die Geschlechterverteilung in der Politik steht.',
})
const home = await queryCollection('content').path("/pages/").first()
const homeAnimated = ref(false);

const homeContent = ref(null);
onMounted(() => {
    if (localStorage.getItem('gwr-home-animated')) {
        homeAnimated.value = true;
        return;
    }
    localStorage.setItem('gwr-home-animated', 'true');
    setTimeout(() => {
        homeContent.value.animate([
            { opacity: 0, transform: 'translateY(0.5rem)' },
            { opacity: 1, transform: 'translateY(0)' }
        ], {
            duration: 750,
            fill: 'forwards',
            easing: 'ease-out'
        });
        homeAnimated.value = true;
    }, 4000);
});

</script>
<template>
    <div class="gwr-default-page">
        <HomeLead class="mt-8"/>
        <div
            :class="homeAnimated ? '' : 'opacity-0'"
            ref="homeContent">
            <ContentRenderer v-if="home" :value="home" class="mt-14"/>
            <p v-else class="text-center text-sm opacity-50 mt-8">Lade...</p>
            <Spacer size="xl" />
            <Footer />
        </div>
    </div>
</template>
