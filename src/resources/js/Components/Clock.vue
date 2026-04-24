<script setup>
import {ref, watch, onUnmounted} from "vue";

const emit = defineEmits(['expired']);

const props = defineProps({
    expires_at: {
        type: String,
    },
    now: {
        type: String,
    },
});

const clock = ref( {
    days: "0",
    hours: "0",
    minutes: "0",
    seconds: "0",
    now: null,
});

// Store interval ID
const timeinterval = ref(null);

watch(
    () => props.now,
    () => {
        clock.value.now = new Date(props.now);
    }
);

const initializeClock = () => {
    let endtime = new Date(props.expires_at);
    clock.value.now = new Date(props.now);

    // Clear previous interval if any
    if (timeinterval.value) {
        clearInterval(timeinterval.value);
    }

    function updateClock() {
        clock.value.now = new Date(Date.parse(clock.value.now) + 1000);
        var t = getTimeRemaining(endtime, clock.value.now);

        clock.value.days = t.days;
        clock.value.hours = ('0' + t.hours).slice(-2);
        clock.value.minutes = ('0' + t.minutes).slice(-2);
        clock.value.seconds = ('0' + t.seconds).slice(-2);

        if (t.total <= 0) {
            clearInterval(timeinterval.value);
            clock.value.days = '00';
            clock.value.hours = '00';
            clock.value.minutes = '00';
            clock.value.seconds = '00';
            expired(); // Emit expired event
        } else {
            clock.value.days = t.days;
            clock.value.hours = ('0' + t.hours).slice(-2);
            clock.value.minutes = ('0' + t.minutes).slice(-2);
            clock.value.seconds = ('0' + t.seconds).slice(-2);
        }
        /*if (t.total <= 0) {
            expired();
        }*/
    }

    updateClock(); // Initial call
    timeinterval.value = setInterval(updateClock, 1000); // Store interval ID
}

const getTimeRemaining = (endtime, now) => {
    var t = Date.parse(endtime) - Date.parse(now);
    var seconds = Math.floor((t / 1000) % 60);
    var minutes = Math.floor((t / 1000 / 60) % 60);
    var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
    var days = Math.floor(t / (1000 * 60 * 60 * 24));

    return {
        'total': t,
        'days': days,
        'hours': hours,
        'minutes': minutes,
        'seconds': seconds
    };
}

const expired = () => {
    emit('expired');
};

// Clear interval on component unmount
onUnmounted(() => {
    if (timeinterval.value) {
        clearInterval(timeinterval.value);
    }
});

defineExpose({
    initializeClock
});
</script>

<template>
    <span class="font-mono text-lg">
        {{ clock.minutes }}:{{ clock.seconds }}
    </span>
</template>

<style scoped>

</style>
