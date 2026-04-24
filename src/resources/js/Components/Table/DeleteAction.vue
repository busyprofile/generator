<script setup>
import Button from 'primevue/button';
</script>

<template>
    <Button
        @click.prevent
        icon="pi pi-trash"
        severity="danger"
        text
        rounded
        aria-label="Удалить"
    >
        <!-- Icon is now set via prop, no slot needed -->
    </Button>
</template>

<style scoped>

</style>
