// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// The canonical origin. Override per-deployment with PUBLIC_SITE_URL so the
// sitemap, canonical tags and Open Graph URLs stay correct on preview builds.
const site = process.env.PUBLIC_SITE_URL || 'https://tebitramson69-netizen.github.io';

// Set PUBLIC_BASE_PATH to '/Student_registration' when publishing to a GitHub
// Pages *project* site. Leave it empty for a custom domain or a user site.
const base = process.env.PUBLIC_BASE_PATH || undefined;

export default defineConfig({
  site,
  base,
  trailingSlash: 'ignore',
  integrations: [sitemap()],
  build: { inlineStylesheets: 'auto' },
  prefetch: { prefetchAll: true, defaultStrategy: 'hover' },
});
