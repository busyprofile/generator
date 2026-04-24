<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <small v-if="message" class="p-error block mt-1">
        {{ message }}
    </small>
</template>
