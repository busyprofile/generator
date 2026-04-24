<script setup>
import { ref } from 'vue';
import InputNumber from 'primevue/inputnumber';

const model = defineModel({
    required: true,
});

const input = ref(null);

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <InputNumber
        v-model="model"
        ref="input"
        inputId="numberInput"
        class="w-full"
    />
</template>
