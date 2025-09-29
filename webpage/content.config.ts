import { defineContentConfig, defineCollection, z } from '@nuxt/content'

export default defineContentConfig({
    collections: {
        report: defineCollection({
            type: 'page',
            source: 'report/*.md',
            schema: z.object({
                lead: z.string()
            })
        })
    }
})
