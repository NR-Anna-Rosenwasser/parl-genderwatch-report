<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    dwid: {
        type: String,
        required: true
    },
    height: {
        type: Number,
        required: false,
        default: 535
    },
    version: {
        type: Number,
        required: false,
        default: 1
    }
});

</script>
<template>
    <div class="max-w-[793px] mx-auto my-8 px-4 lg:px-12">
        <iframe
            :title="title"
            aria-label="Säulendiagramm (gestapelt)"
            :id="`datawrapper-chart-${dwid}`"
            :src="`https://datawrapper.dwcdn.net/${dwid}/${version}/`"
            scrolling="no"
            frameborder="0"
            style="width: 0; min-width: 100% !important; border: none;"
            :height="height"
            data-external="1"
        ></iframe>
    </div>
</template>



<script type="text/javascript">
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
</script>