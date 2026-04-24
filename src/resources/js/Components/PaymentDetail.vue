<script setup>
import {computed, ref} from "vue";
import { useClipboard } from '@vueuse/core'
import Button from 'primevue/button';

const props = defineProps({
    detail: {
        type: String,
    },
    type: {
        type: String,
    },
    name: {
        type: String,
        default: null
    },
    copyable: {
        type: Boolean,
        default: true
    },
    short: {
        type: Boolean,
        default: false
    },
});
const { text, copy, copied, isSupported } = useClipboard()

const phone = computed(() => {
    if ((props.type !== 'phone' && props.type !== 'sim') || !props.detail) {
        return null;
    }

    let x = props.detail.replace(/\D/g, '').match(/(\d{1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);

    return !x || !x[2] ? props.detail : '+' + x[1] + ' (' + x[2] + ') ' + (x[3] ? x[3] + '-' : '') + (x[4] ? x[4] + '-' : '') + x[5];
})

const formattedDetail = computed(() => {
    if (!props.detail) return '';

    switch (props.type) {
        case 'card':
            const cardDetail = props.detail.replace(/\D/g, '');
            if (props.short) {
                return cardDetail.length >= 8 ? `${cardDetail.substring(0, 4)} **** **** ${cardDetail.substring(cardDetail.length - 4)}` : cardDetail;
            } else {
                return cardDetail.match(/.{1,4}/g)?.join(' ') || cardDetail;
            }
        case 'phone':
        case 'sim':
            const formattedPhone = phone.value || props.detail;
            if (props.short) {
                return formattedPhone.length > 10 ? `${formattedPhone.substring(0, 4)} **** ${formattedPhone.substring(formattedPhone.length - 4)}` : formattedPhone;
            }
            return formattedPhone;
        case 'account_number':
            if (props.short) {
                return props.detail.length > 6 ? `***${props.detail.substring(props.detail.length - 6)}` : props.detail;
            }
            return props.detail;
        default:
            return props.detail;
    }
})

const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать');
</script>

<template>
    <div class="flex align-items-center gap-2 items-center">
        <span class="text-nowrap text-color">
            {{ formattedDetail }}
        </span>
        
        <small v-if="name" class="text-xs text-color-secondary ml-1 text-nowrap">
            ({{ name }}) 
        </small>
         <Button 
            v-if="copyable && isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class="w-2rem h-2rem" 
            @click="copy(detail)" 
            v-tooltip.top="copyTooltipText"
            size="small"
        />
    </div>
</template>

<style scoped>

</style>
