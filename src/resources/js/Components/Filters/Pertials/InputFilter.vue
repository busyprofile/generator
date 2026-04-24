<script setup>
import InputText from 'primevue/inputtext';
import {useTableFiltersStore} from "@/store/tableFilters.js";
import {computed} from "vue";

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    name: {
        type: String,
    },
    placeholder: {
        type: String,
    },
});

const model = computed({
    get: () => tableFiltersStore.filters[props.name],
    set: (val) => {
        tableFiltersStore.filters[props.name] = val
    }
})
</script>

<template>
    <div class="field col-12 md:col-4 lg:col-3">
        <InputText
            type="text"
            v-model="model"
            :placeholder="placeholder"
            class="w-full"
        />
    </div>
</template>

<style scoped>

</style>
