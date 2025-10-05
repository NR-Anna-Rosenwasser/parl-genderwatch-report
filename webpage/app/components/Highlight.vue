<script setup>
const props = defineProps({
    icon: {
        type: String,
        required: false,
        default: 'mdi:information'
    }
});
</script>
<template>
    <div class="gwr-highlight__container bg-accentDark text-white p-2 md:p-4">
        <h2 class="gwr-highlight__title mt-0 pt-0 flex gap-2 items-start" v-if="$slots.title">
            <Icon :name="icon" class="mt-1" />
            <slot name="title" mdc-unwrap="p"/>
        </h2>
        <slot />
    </div>
</template>