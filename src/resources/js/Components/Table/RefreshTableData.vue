<script setup>
import {onMounted, onUnmounted, ref} from "vue";
import {router, usePoll} from "@inertiajs/vue3";
import {useTableFiltersStore} from "@/store/tableFilters.js";
import Dropdown from 'primevue/dropdown';

const tableFiltersStore = useTableFiltersStore();

const intervals = ref([
    {name:'Не обновлять', value:0},
    {name:'Каждые 15с', value:15000},
    {name:'Каждые 30с', value:30000},
    {name:'Каждые 60с', value:60000},
]);

const emit = defineEmits(['refreshStarted', 'refreshFinished']);
const storageKey = `refresh-storage-orders`;

const getInitialInterval = () => {
    const storedValue = localStorage.getItem(storageKey);
    if (storedValue === null) return 0;

    let intervalMs = parseInt(storedValue, 10);
    if (isNaN(intervalMs)) return 0;

    if (intervalMs === 5000) intervalMs = 15000;
    if (intervalMs === 10000) intervalMs = 15000;
    if (intervalMs === 20000) intervalMs = 30000;

    const isValidInterval = intervals.value.some(i => i.value === intervalMs);
    return isValidInterval ? intervalMs : 0;
};

const refreshInterval = ref(getInitialInterval());
const offset = ref(100); // Ref for SVG stroke-dashoffset animation

const { start, stop } = usePoll(refreshInterval.value, {
        onStart() {
            emit('refreshStarted');
            // Start animation from full (100) to empty (0) over the interval duration
            animateProgress(0, refreshInterval.value);
        },
        async onFinish() {
            emit('refreshFinished');
        }
    }, {keepAlive: true, autoStart: false}
);

// Animation function using requestAnimationFrame
function animateProgress(targetOffsetValue, duration) {
    const startOffset = 100; // Start from 100 (full circle)
    const targetOffset = targetOffsetValue; // Animate towards this value (0 for full cycle)
    const startTime = performance.now();

    function step(currentTime) {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);
        offset.value = startOffset - (startOffset - targetOffset) * progress;

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            offset.value = targetOffset; // Ensure it ends exactly at the target
        }
    }
    // Reset offset before starting animation
    offset.value = startOffset;
    requestAnimationFrame(step);
}

onMounted(() => {
    if (refreshInterval.value > 0) {
        start();
        // Start initial animation
        animateProgress(0, refreshInterval.value);
    }
});

onUnmounted(() => {
    stop();
});

const storeRefreshInterval = () => {
    localStorage.setItem(storageKey, refreshInterval.value);
}

const reloadPage = () => {
    storeRefreshInterval();
    stop();

    router.visit(route(route().current()), {
        data: tableFiltersStore.getQueryData,
        preserveScroll: true,
        onFinish: () => {
            if (refreshInterval.value > 0) {
                start();
            }
        }
    });
}

</script>

<template>
    <div class="flex items-center gap-3">
        <div v-show="refreshInterval > 0" class="relative w-6 h-6">
            <!-- Background circle -->
            <svg class="w-full h-full absolute top-0 left-0" viewBox="0 0 36 36">
                <path
                    class="[stroke:var(--surface-300)]"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke-width="4"
                />
            </svg>
            <!-- Progress circle -->
            <svg class="w-full h-full absolute top-0 left-0 -rotate-90" viewBox="0 0 36 36">
                <path
                    class="[stroke:var(--primary-color)]"
                    :style="{ strokeDashoffset: offset }"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke-width="4"
                    stroke-dasharray="100, 100"
                />
            </svg>
        </div>
        <!-- Placeholder when refresh is off -->
        <div v-show="refreshInterval === 0" class="w-6 h-6"></div>

        <Dropdown
            v-model="refreshInterval"
            :options="intervals"
            optionLabel="name"
            optionValue="value"
            @change="reloadPage"
            placeholder="Интервал обновления"
            class="w-full md:w-14rem"
        >
        </Dropdown>
    </div>
</template>

<style scoped>

</style>
