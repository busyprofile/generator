<script setup>
import { useClipboard } from '@vueuse/core'
import {computed} from "vue";
import Button from 'primevue/button';

const props = defineProps({
    uuid: {
        type: String,
    },
    copyable: {
        type: Boolean,
        default: true
    }
});

const uuidShort = computed(() => {
    if (!props.uuid) return 'Пусто';
    const items = props.uuid.split('-');
    if (! items.length) {
        return 'Пусто';
    }
    return items[items.length - 1];
});

const { copy, copied, isSupported } = useClipboard();

// Tooltip text for copy button
const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать');
</script>

<template>
    <div class="flex align-items-center gap-1 items-center">
        <span class="text-nowrap font-mono text-sm text-color-secondary" v-tooltip.top="copyable ? uuid : null">
             {{uuidShort}}
        </span>
         <Button 
            v-if="copyable && isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class=" " 
            @click.stop="copy(uuid)" 
            v-tooltip.top="copyTooltipText"
            size="small"
        />
    </div>
</template>

<style scoped>

</style>
