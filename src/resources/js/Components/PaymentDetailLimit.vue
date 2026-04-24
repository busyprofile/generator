<script setup>
import {computed} from "vue";
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    current_daily_limit: {
        type: [String, Number],
        required: true
    },
    daily_limit: {
        type: [String, Number],
        required: true
    },
});

const percent = computed(() => {
    const currentNum = Number(props.current_daily_limit);
    const totalNum = Number(props.daily_limit);
    if (isNaN(currentNum) || isNaN(totalNum) || totalNum === 0) {
        return 0;
    }
    // Ensure percentage doesn't exceed 100 if current > total
    return Math.min((currentNum / totalNum) * 100, 100); 
});

</script>

<template>
    <div>
        <div class="flex justify-content-end mb-1">
            <div class="relative text-nowrap">
                <span
                    class="text-xs font-semibold"
                >
                    {{current_daily_limit}}
                </span>
                <span class="mx-1 text-color-secondary text-xs">из</span>
                <span class="text-xs font-semibold text-color">
                    {{daily_limit}}
                </span>
            </div>
        </div>
        <ProgressBar :value="percent" :showValue="false" :pt="{ root: { class: '!h-1.5' } }"></ProgressBar>
    </div>
</template>

<style scoped>

</style>
