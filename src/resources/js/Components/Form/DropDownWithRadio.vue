<script setup>
import Dropdown from 'primevue/dropdown';

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
    // required: true, // Re-evaluate if needed
});
</script>

<template>
    <!-- Assuming this component is used within a PrimeFlex grid -->
    <div class="field col-12">
         <Dropdown
            v-model="model"
            :options="items"
            :optionLabel="name"
            :optionValue="value"
            :placeholder="label"
            showClear 
            class="w-full"
        />
    </div>
</template>

<style scoped>

</style>
