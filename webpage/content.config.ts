import { defineContentConfig, defineCollection, z } from '@nuxt/content'

export default defineContentConfig({
    collections: {
        content: defineCollection({
            type: 'page',
            source: 'pages/**/*.md'
        }),
        reports: defineCollection({
            type: 'page',
            source: 'reports/**/*.md',
            schema: z.object({
                title: z.string().min(5),
                teaser: z.string().min(10).max(200),
                date: z.date(),
                legislature: z.number(),
                number: z.string(),
            })
        })
    }
})
