<script setup>
import { ref } from 'vue';
// import { useForm } from '@inertiajs/vue3'; // Не используется в текущем варианте
import SectionTitle from '@/Components/SectionTitle.vue';
import { formatDateTime } from '@/utils';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const props = defineProps({
    loginHistory: {
        type: Array,
        required: true,
    },
});

const formatDate = (dateString) => {
    return formatDateTime(dateString);
};

// getStatusClass и getStatusText больше не нужны, логика будет в шаблоне
</script>

<template>
    <section>
        <SectionTitle>
            <template #title>История авторизаций</template>
            <template #description>
                Здесь вы можете просмотреть историю входов в ваш аккаунт.
            </template>
        </SectionTitle>

        <div class="mt-5 space-y-4">
            <template v-if="loginHistory && loginHistory.length > 0">
                <Card v-for="(item, index) in loginHistory" :key="index" class="shadow-md">
                    <template #content>
                        <div class="flex flex-row flex-wrap items-baseline gap-x-6 gap-y-2 text-sm">

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">Дата:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ formatDate(item.created_at) }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">IP:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ item.ip_address }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">Устройство:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ item.device_type }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">Браузер:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ item.browser }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">ОС:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ item.operating_system }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">Локация:</div>
<div class="flex align-items-center gap-1">
<span class="text-nowrap font-mono text-sm text-foreground">{{ item.location }}</span> 
</div>
</div>

<div class="order-detail-group min-w-[120px] flex-shrink-0 text-left">
<div class="text-xs text-muted-foreground mb-0.5">Статус:</div>
<div class="flex align-items-center gap-1">
 <Tag :value="item.is_successful ? 'Успешно' : 'Неудачно'" 
                                     :severity="item.is_successful ? 'success' : 'danger'" 
                                     class="ml-1"/>
</div>
</div>

                            
                        </div>
                    </template>
                </Card>
            </template>
            <template v-else>
                <div class="text-center text-muted-foreground py-4">
                    История авторизаций пуста.
                </div>
            </template>
        </div>
    </section>
</template> 

<style scoped>
::v-deep(.p-card-body) {
padding: 0rem !important;
/* Можно добавить специфичные стили для карточек, если потребуется */
}
</style> 