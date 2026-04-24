<script setup>
import InputBlock from "@/Components/Form/InputBlock.vue";
import InputNumber from 'primevue/inputnumber';

const props = defineProps({
    form: {
        type: Object,
    },
    field: {
        type: String,
    },
    placeholder: {
        type: String,
        default: null
    },
    label: {
        type: String
    },
    helper: {
        type: String,
        default: null,
    },
});

const model = defineModel({
    // required: true, // Re-evaluate if needed
});
</script>

<template>
    <InputBlock
        :form="form"
        :field="field"
        :label="label"
        :helper="helper"
    >
        <InputNumber
            :inputId="field" 
            v-model="model"
            :placeholder="placeholder"
            :invalid="!!form.errors[field]"
            @input="form.clearErrors(field)" 
            class="w-full" 
        />
    </InputBlock>
</template>

<style scoped>

</style>
