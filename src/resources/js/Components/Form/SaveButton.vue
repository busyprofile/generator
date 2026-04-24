<script setup>
import Button from 'primevue/button';
import InlineMessage from 'primevue/inlinemessage';

const emit = defineEmits(['save']);

const props = defineProps({
    disabled: {
        type: Boolean,
    },
    saved: {
        type: Boolean
    },
});

const handleClick = () => {
    emit('save');
};
</script>

<template>
    <div class="flex items-center gap-4">
        <Button 
            type="submit"
            label="Сохранить" 
            icon="pi pi-check" 
            :disabled="disabled"
            @click="handleClick"
        />
        <InlineMessage v-if="saved" severity="success">Сохранено.</InlineMessage>
    </div>
</template>

<style scoped>

</style>
