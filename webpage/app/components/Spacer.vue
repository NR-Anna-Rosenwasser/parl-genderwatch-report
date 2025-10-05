<script setup>
const props = defineProps({
    size: {
        type: String,
        required: false,
        default: 'base'
    }
});
</script>
<template>
    <div :class="`gwr-spacer__${props.size}`"></div>
</template>

<style scoped lang="scss">
.gwr-spacer {
    &__base {
        @apply h-10 md:h-14;
    }

    &__lg {
        @apply h-16 md:h-20;
    }

    &__xl {
        @apply h-24 md:h-32;
    }

    &__sm {
        @apply h-6 md:h-8;
    }
}
</style>