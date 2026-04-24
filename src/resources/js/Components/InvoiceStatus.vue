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
        case 'success': return 'Завершена';
        case 'fail': return 'Отклонена';
        case 'pending': return 'Ожидает';
        default: return props.status; // Fallback to original status
    }
});

// Compute tag severity based on status
const tagSeverity = computed(() => {
    switch (props.status) {
        case 'success': return 'success';
        case 'fail': return 'danger';
        case 'pending': return 'warning';
        default: return 'info';
    }
});
</script>

<template>
    <Tag :value="tagValue" :severity="tagSeverity"></Tag>
</template>
