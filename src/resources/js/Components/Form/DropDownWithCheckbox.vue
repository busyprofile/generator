<script setup>
import { computed } from "vue"
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
    label: {
        type: String,
    },
    items: {
        type: Array,
    },
    value: {
        type: String,
    },
    name: {
        type: String,
    },
});

const model = defineModel({
    // Assuming model passed via v-model is an array
    // required: true, // Re-evaluate if required is necessary or add default
});

</script>

<template>
    <!-- Assuming this component is used within a PrimeFlex grid -->
    <div class="field col-12">
        <MultiSelect
            v-model="model"
            :options="items"
            :optionLabel="name" 
            :optionValue="value"
            :placeholder="label"
            display="chip"
            class="w-full"
        />
    </div>
</template>

<style scoped>

</style>
