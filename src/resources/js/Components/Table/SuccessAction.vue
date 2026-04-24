<script setup>
import Button from 'primevue/button';
</script>

<template>
    <Button
        @click.prevent
        icon="pi pi-check"
        label="Принять"
        size="small"
        severity="success"
    >
        <!-- Icon and Label are set via props -->
    </Button>
</template>

<style scoped>

</style>
