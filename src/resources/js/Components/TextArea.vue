<script setup>
import Textarea from 'primevue/textarea';

const model = defineModel({
    required: true,
});
defineProps({
    rows: {
        type: Number,
        default: 4
    },
    required: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <Textarea
        v-model="model"
        :rows="rows"
        :required="required"
        class="w-full"
    />
</template>
