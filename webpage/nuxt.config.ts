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
        head: {
            script: [
                {
                    src: "https://analytics.toes.ch/api/script.js",
                    async: true,
                    defer: true,
                    'data-site-id': '37c2809b7bd1',
                }
            ],
            link: [
                {
                    rel: 'icon', type: 'image/png', href: '/images/favicon/favicon-96x96.png', sizes: '96x96'
                },
                {
                    rel: 'icon', type: 'image/svg+xml', href: '/images/favicon/favicon.svg'
                },
                {
                    rel: 'shortcut icon', href: '/images/favicon/favicon.ico'
                },
                {
                    rel: 'apple-touch-icon', sizes: '180x180', href: '/images/favicon/apple-touch-icon.png'
                },
                {
                    rel: 'manifest', href: '/images/favicon/site.webmanifest'
                }
            ]
        }
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