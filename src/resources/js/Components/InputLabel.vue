<script setup>
defineProps({
    value: {
        type: String,
    },
    error: {
        type: Boolean,
        default: false,
    }
});
</script>

<template>
    <label 
        class="block text-sm font-medium mb-1" 
        :class="{ 'p-error': error }"
    >
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
