import { defineNuxtConfig } from 'nuxt/config'
// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    devtools: { enabled: true },
    css: ['assets/css/main.scss'],
    modules: [
        '@nuxt/content',
        '@nuxt/fonts',
        '@nuxtjs/tailwindcss',
        '@nuxt/icon'
    ],
    tailwindcss: {
        exposeConfig: true,
    },
    app: {
        pageTransition: { name: 'page', mode: 'out-in' },
    },
    vite: {
        esbuild: {
            drop: ['console', 'debugger'],
        },
    },
    runtimeConfig: {
        public: {
            apiUrl: process.env.API_URL || 'http://localhost:8000/api/v1',
            currentSession: process.env.CURRENT_SESSION || 5210,
        },
    },
})