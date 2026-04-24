<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    status: {
        type: String,
    },
});

// Compute tag value based on status
const tagValue = computed(() => {
    switch (props.status) {
        case 'accepted': return 'Принят';
        case 'canceled': return 'Отклонен';
        case 'pending': return 'Ожидает';
        default: return props.status; // Fallback
    }
});

// Compute tag severity based on status
const tagSeverity = computed(() => {
    switch (props.status) {
        case 'accepted': return 'success';
        case 'canceled': return 'danger';
        case 'pending': return 'warn';
        default: return 'info';
    }
});
</script>

<template>
    <Tag 
        :value="tagValue" 
        :severity="tagSeverity" 
        :icon="props.status === 'pending' ? 'pi pi-spin pi-spinner' : null"
    >
    </Tag>
</template>
