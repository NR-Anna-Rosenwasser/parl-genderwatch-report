<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    dwid: {
        type: String,
        required: true
    },
    height: {
        type: Number,
        required: false,
        default: 400
    },
    version: {
        type: Number,
        required: false,
        default: 1
    }
});

onMounted(() => {
    (function () {
        "use strict";
        window.addEventListener("message", function (event) {
            if (event.data["datawrapper-height"] !== undefined) {
                var iframes = document.querySelectorAll("iframe");
                for (var chartId in event.data["datawrapper-height"]) {
                    for (var i = 0, iframe; (iframe = iframes[i]); i++) {
                        if (iframe.contentWindow === event.source) {
                            var newHeight = event.data["datawrapper-height"][chartId] + "px";
                            iframe.style.height = newHeight;
                        }
                    }
                }
            }
        });
    })();
});
</script>

<template>
    <div class="my-6 md:my-10 bg-white p-4 max-w-md mx-auto shadow-lg">
        <iframe
            :title="title"
            :id="`datawrapper-chart-${dwid}`"
            :aria-label="`Grafik zu ${title}`"
            :src="`https://datawrapper.dwcdn.net/${dwid}/${version}/`"
            :height="height"
            scrolling="no"
            frameborder="0"
            style="width: 0; min-width: 100% !important; border: none;"
            data-external="1"
        ></iframe>
    </div>
</template>