<script setup>
import { useClipboard } from '@vueuse/core'
import { computed } from 'vue';
import Button from 'primevue/button';

const props = defineProps({
    text: {
        type: String,
        required: true // Assuming text is always required
    },
});
const { copy, copied, isSupported } = useClipboard();

// Shortened text display
const textShort = computed(() => {
    if (!props.text) return '';
    // Apply shortening logic
    return props.text.length > 16 
        ? props.text.substring(0, 8) + '...' +  props.text.substring(props.text.length - 8)
        : props.text;
});

// Tooltip text for copy button
const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать');
</script>

<template>
    <div class="flex align-items-center gap-1">
        <span class="text-nowrap font-mono text-sm text-color-secondary" v-tooltip.top="text || null">
            {{ textShort }}
        </span>
         <Button 
            v-if="isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class="w-2rem h-2rem" 
            @click.stop="copy(text)" 
            v-tooltip.top="copyTooltipText"
            size="small"
        />
    </div>
</template>

<style scoped>

</style>
