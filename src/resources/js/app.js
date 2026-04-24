import './bootstrap';
import '../css/app.css';
import 'flowbite';
import "../css/primevue.css";

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { Ziggy } from './ziggy-routes.js';
import { createPinia } from 'pinia';

import PrimeVue from 'primevue/config';
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import Tooltip from "primevue/tooltip";
import "primeicons/primeicons.css";
import Nora from "@primeuix/themes/nora";
import { definePreset } from "@primeuix/themes";

const CyanPreset = definePreset(Nora, {
    semantic: {
        primary: {
            50:  '#ecfeff',
            100: '#cffafe',
            200: '#a5f3fc',
            300: '#67e8f9',
            400: '#43E3F4',
            500: '#22d3ee',
            600: '#06b6d4',
            700: '#0891b2',
            800: '#0e7490',
            900: '#155e75',
            950: '#083344',
        },
        colorScheme: {
            dark: {
                primary: {
                    color: '#43E3F4',
                    contrastColor: '#062830',
                    hoverColor: '#22d3ee',
                    activeColor: '#06b6d4',
                },
                highlight: {
                    background: 'rgba(67, 227, 244, 0.16)',
                    focusBackground: 'rgba(67, 227, 244, 0.24)',
                    color: '#43E3F4',
                    focusColor: '#43E3F4',
                },
            },
            light: {
                primary: {
                    color: '#43E3F4',
                    contrastColor: '#062830',
                    hoverColor: '#22d3ee',
                    activeColor: '#06b6d4',
                },
                highlight: {
                    background: 'rgba(67, 227, 244, 0.16)',
                    focusBackground: 'rgba(67, 227, 244, 0.24)',
                    color: '#43E3F4',
                    focusColor: '#43E3F4',
                },
            },
        },
    },
});

const pinia = createPinia()

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// VITE_DISABLE_CONSOLE_LOGS в .env
if (import.meta.env.VITE_DISABLE_CONSOLE_LOGS === 'true') {
    window._originalConsole = {
        log: console.log,
        warn: console.warn,
        error: console.error,
        info: console.info,
        debug: console.debug
    };
    
    console.log = () => {};
    console.warn = () => {};
    console.error = () => {};
    console.info = () => {};
    console.debug = () => {};
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const myApp = createApp({ render: () => h(App, props) })
  
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: CyanPreset,
                    options: {
                        prefix: "p",
                        darkModeSelector: ".dark",
                        cssLayer: false,
                    },
                },
            })
            .use(ToastService)
            .use(ConfirmationService);
            
        // Регистрируем директиву tooltip
        myApp.directive('tooltip', Tooltip);

        myApp.config.globalProperties.appName = appName;

        myApp.mount(el);

        return myApp;
    },
    progress: {
        color: '#4B5563',
    },
});
