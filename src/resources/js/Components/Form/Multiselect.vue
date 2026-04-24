<script setup>
import Dropdown from 'primevue/dropdown';

// Define Model for v-model binding (Vue 3.3+)
const model = defineModel({
    // Type depends on what props.valueKey represents (string, number, object)
    // default: null // or appropriate default for single select
});

const props = defineProps({
    options: {
        type: Array,
        required: true
    },
    labelKey: {
        type: String,
        default: 'label'
    },
    valueKey: {
        type: String,
        default: 'value'
    },
    enableSearch: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'Выберите опции'
    },
});
</script>

<template>
    <div class="field col-12">
        <Dropdown
            v-model="model" 
            :options="options"
            :optionLabel="labelKey"
            :optionValue="valueKey"
            :placeholder="placeholder"
            :filter="enableSearch" 
            showClear 
            class="w-full"
        />
    </div>
</template>
