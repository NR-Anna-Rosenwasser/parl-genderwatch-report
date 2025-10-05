<script setup>
import { ref } from 'vue';

const isDefaultOpen = false;
const isOpen = ref(isDefaultOpen);
const title = ref(null);

onMounted(() => {
    title.value.addEventListener('click', () => {
        const content = title.value.closest('.gwr-toggle').querySelector('.gwr-toggle__content');
        const inner = content.querySelector('.gwr-toggle__content__inner');
        const icon = title.value.querySelector('.iconify');
        if (isOpen.value) {
            content.animate([
                { maxHeight: content.scrollHeight + 'px', offset: 0 },
                { maxHeight: '0px', offset: 1 }
            ], {
                duration: 300,
                easing: 'ease-in-out',
                fill: 'forwards'
            });
            icon.animate([
                { transform: 'rotate(180deg)', offset: 0 },
                { transform: 'rotate(0deg)', offset: 1 }
            ], {
                duration: 300,
                easing: 'ease-in-out',
                fill: 'forwards'
            });
            inner.animate([
                { transform: 'translateY(0)', opacity: 1, offset: 0 },
                { transform: 'translateY(10px)', opacity: 0, offset: 1 }
            ], {
                duration: 300,
                easing: 'ease-in-out',
                fill: 'forwards'
            });
        } else {
            content.animate([
                { maxHeight: '0px', transform: 'translateY(10px)', offset: 0 },
                { maxHeight: content.scrollHeight + 'px', transform: 'translateY(0)', offset: 0.999 },
                { maxHeight: 'none', transform: 'translateY(0)', offset: 1 }
            ], {
                duration: 300,
                easing: 'ease-in-out',
                fill: 'forwards'
            });
            icon.animate([
                { transform: 'rotate(0deg)', offset: 0 },
                { transform: 'rotate(180deg)', offset: 1 }
            ], {
                duration: 300,
                easing: 'ease-in-out',
                fill: 'forwards'
            });
            setTimeout(() => {
                inner.animate([
                    { transform: 'translateY(10px)', opacity: 0, offset: 0 },
                    { transform: 'translateY(0)', opacity: 1, offset: 1 }
                ], {
                    duration: 300,
                    easing: 'ease-in-out',
                    fill: 'forwards'
                });
            }, 250);
        }
        isOpen.value = !isOpen.value;
    });
});

</script>
<template>
    <div class="gwr-toggle py-2 border-t-2 border-b-2 border-accentDark mt-6">
        <div class="gwr-toggle__title flex justify-between items-center cursor-pointer" ref="title">
            <h5 class="mt-0 pt-0 mb-0 font-inter"><slot name="title" mdc-unwrap="p"/></h5>
            <Icon name="mdi:chevron-down" class="text-3xl"/>
        </div>
        <div class="gwr-toggle__content max-h-0 overflow-hidden">
            <div
                class="gwr-toggle__content__inner py-4"
                :class="isDefaultOpen ? 'opacity-100' : 'opacity-0'"
            >
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.gwr-toggle {
    &+& {
        margin-top: -2px;
    }
}
</style>