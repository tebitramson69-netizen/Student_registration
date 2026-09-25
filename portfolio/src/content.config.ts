import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

/**
 * Projects are Markdown files in `src/content/projects/`. The schema below is
 * enforced at build time — a missing or malformed field fails `npm run build`
 * rather than silently rendering an empty section.
 */
const projects = defineCollection({
  loader: glob({ base: './src/content/projects', pattern: '**/*.md' }),
  schema: z.object({
    title: z.string(),
    /** One line for cards and search results. */
    summary: z.string(),
    /** Shown under the title on the case-study page. */
    role: z.string(),
    category: z.string(),
    /** Lower sorts first. Controls order everywhere. */
    order: z.number(),
    featured: z.boolean().default(false),
    year: z.string(),
    /** Honest status label. Rendered as-is. */
    status: z.string(),
    tech: z.array(z.string()).min(1),
    repo: z.string().url().optional(),
    demo: z.string().url().optional(),
    /** Verified, countable facts only — never invented metrics. */
    metrics: z
      .array(z.object({ value: z.string(), label: z.string() }))
      .optional(),
    /** Screenshot relative to `public/`. Omitted renders a designed fallback. */
    image: z.string().optional(),
    imageAlt: z.string().optional(),
  }),
});

export const collections = { projects };
