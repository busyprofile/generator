<script setup>
import { useClipboard } from '@vueuse/core'
import { computed } from 'vue'
import Button from 'primevue/button'

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    copy_text: {
        type: String,
        required: true
    },
});
const { copy, copied, isSupported } = useClipboard()

// Tooltip text for copy button
const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать')
</script>

<template>
    <div class="flex align-items-center gap-1">
        <span>{{ text }}</span>
        <Button 
            v-if="isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class="w-2rem h-2rem" 
            @click.stop="copy(copy_text)" 
            v-tooltip.top="copyTooltipText"
        />
    </div>
</template>

<style scoped>

</style>
