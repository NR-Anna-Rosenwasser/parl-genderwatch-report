<script setup>
import {ref} from 'vue'
import { CountUp } from 'countup.js';
let maleElement = ref(null);
let femaleElement = ref(null);
let taglineElement = ref(null);
import { useRuntimeConfig } from '#app'
const config = useRuntimeConfig();
const response = await useFetch(`https://api.gwr.rose-water.ch/api/v1/transcripts/basic-distribution/5210?percentages=0`);

onMounted(async () => {
    const maleCountUp = new CountUp(maleElement.value, response.data.value.male / 60, {
        separator: "'",
        duration: 1
    });
    maleCountUp.start();

    setTimeout(() => {
        const femaleCountUp = new CountUp(femaleElement.value, response.data.value.female / 60, {
            separator: "'",
            duration: 1,
        });
        femaleCountUp.start();

        setTimeout(() => {
            taglineElement.value.animate([
                { opacity: 0, transform: 'translateY(0.5rem)' },
                { opacity: 1, transform: 'translateY(0)' }
            ], {
                duration: 750,
                fill: 'forwards',
                easing: 'ease-out'
            });
        }, 1500);
    }, 1000);
});

</script>


<template>
    <div class="gwr-home-lead">
        <p class="font-cooper text-4xl">
            In der letzten Session haben Männer <span class="text-accent"><span ref="maleElement">0</span> Minuten</span> lang gesprochen. Frauen in derselben Zeit nur <span class="text-accent"><span ref="femaleElement">0</span> Minuten.</span>
        </p>
        <p class="mt-4 text-2xl font-extrabold opacity-0" ref="taglineElement">Es ist Zeit, <span class="text-accent">dass sich das ändert.</span></p>
    </div>
</template>