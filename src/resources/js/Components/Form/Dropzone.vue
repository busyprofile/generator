<script setup>
import { computed } from "vue";
import FileUpload from 'primevue/fileupload';

const model = defineModel({
    // required: true, // Re-evaluate if needed
});

const props = defineProps({
    title: {
        type: String,
        default: 'Нажмите, чтобы загрузить файл',
    },
    description: {
        type: String,
        default: null,
    },
});

// Handler for file selection
const onFileSelect = (event) => {
    if (event.files && event.files.length > 0) {
        model.value = event.files[0]; // Update the model with the selected file object
    }
};

// Dynamically set the button label
const chooseButtonLabel = computed(() => {
    return model.value ? model.value.name : props.title;
});
</script>

<template>
    <div class="field col-12">
        <FileUpload 
            mode="basic" 
            name="file" 
            :chooseLabel="chooseButtonLabel" 
            :maxFileSize="10000000" 
            @select="onFileSelect"
            class="w-full"
            :invalidChooseButtonLabel="'Выбрать файл'"
        />
        <!-- Optional: Display description if needed -->
        <small v-if="description && !model" class="mt-1 p-error block">{{ description }}</small>
    </div>
</template>

<style scoped>

</style>
