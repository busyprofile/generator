<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import DateTime from '@/Components/DateTime.vue';
import {ref, computed, onMounted, onUnmounted} from "vue";
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const devices = ref(usePage().props.devices.data);

const form = useForm({
    name: '',
});

const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const buttonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });

const submit = () => {
    form.post(route('trader.devices.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

router.on('success', (event) => {
    devices.value = usePage().props.devices.data;
})

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        alert('Токен скопирован в буфер обмена');
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Устройства" />

        <MainTableSection title="Устройства" :data="devices" :paginate="false">
            <template v-slot:header>
                <Card class="shadow-md rounded-plate mb-6">
                    <template #title>
                        <h3 class="text-lg">Скачайте и установите APK</h3>
                    </template>
                    <template #content>
                        <p class="text-base font-normal text-gray-600 dark:text-gray-400">
                            Для получения СМС нужно приложение, которое доступно только для Android - <a :href="route('app.download')" class="text-blue-500">Скачать</a>
                        </p>
                    </template>
                </Card>
                <Card class="shadow-md rounded-plate">
                     <template #title>
                         <h2 class="text-lg">Создать новый токен для устройства</h2>
                     </template>
                     <template #subtitle>
                         <p class="mt-1 text-sm">
                            Создайте новый токен для подключения устройства. Один токен может быть использован только для одного устройства.
                        </p>
                     </template>
                     <template #content>
                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div class="flex flex-col gap-2">
                                <label for="name" class="dark:text-white">Название устройства</label>
                                <InputText
                                    id="name"
                                    type="text"
                                    class="w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="Например: Samsung Galaxy S21"
                                    :invalid="form.errors.name !== undefined"
                                    :size="buttonSize"
                                />
                                <small v-if="form.errors.name" class="p-error">
                                    {{ form.errors.name }}
                                </small>
                            </div>

                            <div class="flex items-center gap-4">
                                <Button
                                    label="Создать токен"
                                    type="submit"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                    :size="buttonSize"
                                />
                                <Message v-if="form.recentlySuccessful" severity="success" :closable="false" class="text-sm">Токен создан.</Message>
                            </div>
                        </form>
                     </template>
                </Card>
            </template>

            <template v-slot:body>
                <DataTable :value="devices" stripedRows class="w-full" size="small">
                    <Column field="name" header="Название" />
                    <Column header="Токен">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span class="truncate max-w-[140px] font-mono text-xs text-foreground">{{ data.token }}</span>
                                <Button
                                    icon="pi pi-copy"
                                    text rounded severity="secondary"
                                    size="small"
                                    @click="copyToClipboard(data.token)"
                                    v-tooltip.top="'Копировать токен'"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column header="Статус">
                        <template #body="{ data }">
                            <Tag
                                :value="data.android_id ? 'Подключено' : 'Не подключено'"
                                :severity="data.android_id ? 'success' : 'warn'"
                            />
                        </template>
                    </Column>
                    <Column header="Создан">
                        <template #body="{ data }">
                            <DateTime class="justify-start" :data="data.created_at" />
                        </template>
                    </Column>
                    <Column header="Подключен">
                        <template #body="{ data }">
                            <DateTime v-if="data.connected_at" class="justify-start" :data="data.connected_at" />
                            <span v-else class="text-muted-foreground">—</span>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </MainTableSection>
    </div>
</template>
