<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import InputBlock from "@/Components/Form/InputBlock.vue";

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
    required: true,
});
</script>

<template>
    <div>
        <InputBlock
            :form="form"
            :field="field"
            :label="label"
            :helper="helper"
        >
            <TextInput
                :id="field"
                v-model="model"
                type="text"
                class="block w-full"
                :placeholder="placeholder"
                :error="!!form.errors[field]"
                @input="form.clearErrors(field)"
            />
        </InputBlock>
    </div>
</template>

<style scoped>

</style>
