<script setup>
import Button from 'primevue/button';
import { router } from '@inertiajs/vue3';

defineProps({
    link: {
        type: String,
        required: true // Assume link is always required
    },
});

const navigate = (url) => {
    if (url) {
        router.visit(url);
    }
};
</script>

<template>
    <Button 
        icon="pi pi-pencil" 
        label="Редактировать"
        size="small"
        severity="secondary" 
        @click="navigate(link)"
    >
        <!-- Icon is set via prop -->
    </Button>
</template>

<style scoped>

</style>
