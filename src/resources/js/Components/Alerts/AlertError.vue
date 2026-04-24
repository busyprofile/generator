<script setup>
import Message from 'primevue/message';

const props = defineProps({
    message: String,
});
</script>

<template>
    <Message 
        v-if="message" 
        severity="error" 
        :closable="false"
        class="mb-6 w-full"
    >
        <span class="font-medium">Внимание!</span> {{ message }}
    </Message>
</template>
