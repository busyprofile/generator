<script setup>
import {computed} from "vue";

const props = defineProps({
    text: {
        type: String,
    },
    tooltip: {
        type: String,
        default: ''
    },
    popover: {
        type: String,
        default: ''
    },
    trim: {
        type: Number,
        default: 0
    },
});

const textFormated = computed(() => {
    var text = props.text;
    var textLength = text.length

    if (props.trim > 0 && props.trim < textLength) {
        var startPartLength = Math.round(props.trim / 2 - 2);
        var endPartLength = Math.round(props.trim / 2 - 1);

        text = text.substring(0, startPartLength) + '...' + text.substring(textLength - endPartLength, textLength);
    }

    return text;
})

// Determine the content for the tooltip directive
const tooltipContent = computed(() => props.tooltip || props.popover || null);
</script>

<template>
    <span v-tooltip.top="tooltipContent">
        {{ textFormated }}
    </span>
</template>

<style scoped>

</style>
