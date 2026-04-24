<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useClipboard } from "@vueuse/core";
import { ref, computed, onMounted } from 'vue';

// PrimeVue Imports
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputGroup from 'primevue/inputgroup';
import InputText from 'primevue/inputtext';

// Markdown and Highlighting
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/atom-one-dark.css';

// Import Markdown content as a raw string
import rawDocContent from '@/../docs/1.0/overview.md?raw';

const user = usePage().props.auth.user;
const token = usePage().props.token;

const { text, copy, copied } = useClipboard({ source: token });

const openDocs = () => {
    const docsElement = document.getElementById('api-documentation-content');
    if (docsElement) {
        docsElement.scrollIntoView({ behavior: 'smooth' });
    }
};

const md = new MarkdownIt({
  html: true,
  linkify: true,
  typographer: true,
  highlight: function (str, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try {
        return '<pre class="hljs"><code>' +
               hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
               '</code></pre>';
      } catch (__) {}
    }
    return '<pre class="hljs"><code>' + md.utils.escapeHtml(str) + '</code></pre>';
  }
});

const renderedDocumentation = computed(() => {
  if (rawDocContent) {
    const modifiedContent = rawDocContent.replace(/<span style="color:red;">\*<\/span>/g, '<strong class="required-star">*</strong>');
    return md.render(modifiedContent);
  }
  return '';
});

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head title="Интеграция по API"/>

    <div>
        <section>
            <div class="mx-auto space-y-8">
                <div>
                    <div class="flex justify-between items-start">
                        <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">Интеграция по API</h2>
                        <Button
                            label="К документации"
                            icon="pi pi-arrow-down"
                            @click="openDocs"
                            outlined
                            class="hidden sm:inline-flex"
                         />
                    </div>

                    <Card class="mt-5 w-full max-w-lg">
                         <template #content>
                            <label for="api-key" class="text-sm font-medium text-gray-900 dark:text-white mb-2 block">Ваш API токен:</label>
                            <InputGroup>
                                <InputText
                                    id="api-key"
                                    :value="token"
                                    readonly
                                    class="w-full"
                                />
                                <Button
                                    :icon="copied ? 'pi pi-check' : 'pi pi-copy'"
                                    :severity="copied ? 'success' : 'secondary'"
                                    @click="copy()"
                                    v-tooltip.bottom="copied ? 'Скопировано!' : 'Скопировать'"
                                />
                            </InputGroup>
                         </template>
                    </Card>
                </div>

                <Card id="api-documentation-content">
                    <template #title>
                        <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">Документация API</h3>
                    </template>
                    <template #content>
                        <div class="prose dark:prose-invert max-w-none" v-html="renderedDocumentation"></div>
                    </template>
                </Card>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Basic styling for rendered Markdown content */
:deep(.prose) {
    line-height: 1.6;
}

/* Hide the first H1 generated from markdown as it repeats the Card title */
:deep(.prose > h1:first-child) {
    display: none;
}

:deep(.prose h1) { /* Markdown h1 (now second h1 if exists) */
    font-size: 1.75rem; /* Slightly smaller than Card title */
    margin-bottom: 1rem;
    margin-top: 2.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--p-surface-300);
    font-weight: 600;
}
:deep(.prose h2) { /* Markdown h2 */
    font-size: 1.5rem;
    margin-bottom: 1rem;
    margin-top: 2.5rem; /* Increased top margin */
    padding-bottom: 0.5rem; /* Added padding */
    border-bottom: 1px solid var(--p-surface-300); /* Added border */
    font-weight: 600;
}
:deep(.prose h3) { /* Markdown h3 */
    font-size: 1.25rem;
    margin-bottom: 0.75rem; /* Increased bottom margin */
    margin-top: 1.5rem; /* Increased top margin */
    font-weight: 600;
}

:deep(.prose p) {
    margin-bottom: 1rem;
}

:deep(.prose a) {
    color: var(--p-primary-500);
    text-decoration: none;
    font-weight: 500; /* Slightly bolder links */
}
:deep(.prose a:hover) {
    text-decoration: underline;
}

:deep(.prose pre.hljs) {
    background-color: #282c34;
    color: #abb2bf;
    padding: 1em;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-top: 0.5rem; /* Added top margin */
    margin-bottom: 1.5rem; /* Increased bottom margin */
}

:deep(.prose code:not(pre code)) { /* Inline code */
 
    color: var(--p-text-color);
    padding: 0.2em 0.4em;
    margin: 0 0.1em;
    font-size: 85%;
    border-radius: 3px;
    font-family: var(--p-font-family-mono); /* Use monospace font */
}
:deep(.prose pre.hljs code) {
   
    padding: 0;
    font-size: inherit;
    color: inherit; /* Inherit color from pre */
}


:deep(.prose table) {
    width: 100%;
    margin-top: 1rem; /* Added top margin */
    margin-bottom: 1.5rem; /* Increased bottom margin */
    border-collapse: collapse;
    box-shadow: 0px 0px 0px 1px var(--surface-border); /* Subtle shadow */
    border-radius: 0.5rem; /* Rounded corners for table */
    overflow: hidden; /* Clip shadow */
}

:deep(.prose th, .prose td) {
    border: none; /* Remove individual borders */
    border-bottom: 1px solid var(--surface-border); /* Add bottom border for separation */
    padding: 0.75rem 1rem; /* Increased padding */
    text-align: left;
    vertical-align: top; /* Align text to top */
}
:deep(.prose th) {
    
    font-weight: 600;
    border-bottom-width: 2px; /* Make header separator thicker */
    border-color: var(--surface-border);
}

/* Zebra stripes */
:deep(.prose tbody tr:nth-child(odd)) {
  
}
:deep(.prose tbody tr:last-child td) {
    border-bottom: none; /* Remove border from last row */
}

/* Fix inline code inside tables */
:deep(.prose td code:not(pre code)) {
   
    /* color: var(--p-primary-700); */ /* Optional: different color for code in tables */
}

:deep(.prose ul, .prose ol) {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
    margin-top: 0.5rem; /* Added top margin */
}
:deep(.prose li) {
    margin-bottom: 0.35rem; /* Slightly increased spacing */
}

:deep(.required-star) {
    color: var(--p-red-500);
    font-weight: bold;
}
 
 
</style>

