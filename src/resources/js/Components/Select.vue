<script setup>
import Dropdown from 'primevue/dropdown';

const model = defineModel({
    required: true,
});
defineProps({
    items: {
        type: Object,
    },
    value: {
        type: String,
    },
    name: {
        type: String,
    },
    default_title: {
        type: String,
    },
    required: {
        type: Boolean,
        default: true,
    },
});

</script>

<template>
    <Dropdown
        :required="required"
        v-model="model"
        :options="items"
        :optionLabel="name"
        :optionValue="value"
        :placeholder="default_title"
        showClear
        class="w-full"
    />
</template>

<style scoped>

</style>
