<script setup>
import Message from 'primevue/message';

const props = defineProps({
    message: String,
});
</script>

<template>
    <Message
        v-if="message"
        severity="info"
        :closable="false"
        class="mb-4 w-full"
    >
        <span class="font-medium">Информация!</span> {{ message }} 
    </Message>
</template>
