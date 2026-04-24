<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {useClipboard} from "@vueuse/core";

const user = usePage().props.auth.user;
const token = usePage().props.token;

const { text, copy, copied } = useClipboard()

const openDocs = () => {
    window.open('/docs', '_blank');
}

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <Head title="Интеграция по API"/>

    <div>
        <section class="antialiased">
            <div class="mx-auto">
                <div class="mx-auto">
                    <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl mb-6">Интеграция по API</h2>

                    <h3 class="mb-1.5 text-lgleading-none text-gray-900 dark:text-white">
                        Изучите инструкцию по интеграции сервиса
                    </h3>
                    <p class="text-base text-gray-500 dark:text-gray-400">
                        <a @click.prevent="openDocs" href="#" class="text-blue-500 hover:text-blue-600">Открыть документацию</a>
                    </p>


                    <div class="mt-5 w-full max-w-lg bg-white dark:bg-gray-800 shadow-md rounded-plate p-5">
                        <label for="api-key" class="text-sm font-medium text-gray-900 dark:text-white mb-2 block">API токен:</label>
                        <div class="relative mb-4">
                            <input
                                id="api-key"
                                type="text"
                                class="col-span-6 bg-gray-50 border border-gray-300 text-gray-500 text-sm rounded-xl  focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                :value="token"
                                disabled
                                readonly
                            >
                            <button
                                data-copy-to-clipboard-target="api-key"
                                data-tooltip-target="tooltip-api-key"
                                @click="copy(token)"
                                class="absolute end-2 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl  p-2 inline-flex items-center justify-center"
                            >
                                    <span v-if="!copied" id="default-icon-api-key">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                             viewBox="0 0 18 20">
                                            <path
                                                d="M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z"/>
                                        </svg>
                                    </span>
                                <span v-else id="success-icon-api-key" class="inline-flex items-center">
                                        <svg class="w-3.5 h-3.5 text-blue-700 dark:text-blue-500" aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 12">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M1 5.917 5.724 10.5 15 1.5"/>
                                        </svg>
                                    </span>
                            </button>
                            <div id="tooltip-api-key" role="tooltip"
                                 class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-xl  shadow-sm opacity-0 tooltip dark:bg-gray-700">
                                <span v-if="!copied" id="default-tooltip-message-api-key">Скопировать</span>
                                <span v-else id="success-tooltip-message-api-key">Скопировано!</span>
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template> 