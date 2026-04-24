<script setup>
import Button from 'primevue/button';
import { router } from '@inertiajs/vue3';

defineProps({
    link: {
        type: String,
        default: null
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
        icon="pi pi-eye"
        label="Посмотреть"
        size="small"
        severity="info" 
     
        @click="navigate(link)"
    />
</template>

<style scoped>

</style>
