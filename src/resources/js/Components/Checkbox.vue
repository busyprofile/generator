<script setup>
import { computed } from 'vue';
import Checkbox from 'primevue/checkbox';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <Checkbox
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
    />
</template>
