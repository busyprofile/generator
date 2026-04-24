<script setup>
import { ref } from 'vue';
import InputText from 'primevue/inputtext';

const model = defineModel({
    required: true,
});

const input = ref(null);

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <InputText
        v-model="model"
        ref="input"
        type="text"
        class="w-full"
    />
</template>
