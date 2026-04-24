<script setup>
import { useClipboard } from '@vueuse/core'
import {computed} from "vue";
import Button from 'primevue/button';

const props = defineProps({
    id: {
        type: String,
    },
    copyable: {
        type: Boolean,
        default: true
    }
});

const idShort = computed(() => {
    if (!props.id) {
        return 'Пусто';
    }

    if (props.id.length > 8) {
        const last = props.id.substring(props.id.length - 8);
        return `${last}`;
    }

    return props.id;
});

const { copy, copied, isSupported } = useClipboard()

// Tooltip text for copy button
const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать');
</script>

<template>
    <div class="flex align-items-center gap-1">
        <span class="text-nowrap font-mono text-sm text-color-secondary" v-tooltip.top="copyable ? id : null">
             {{idShort}}
        </span>
         <Button 
            v-if="copyable && isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class="w-2rem h-2rem" 
            @click.stop="copy(id)" 
            v-tooltip.top="copyTooltipText"
        />
    </div>
</template>

<style scoped>

</style>
