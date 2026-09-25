/**
 * Copy the Sveltia CMS browser build into `public/admin/cms/`.
 *
 * Why vendor it instead of loading it from a CDN:
 *
 *  1. /admin holds write access to the repository. A script loaded from a
 *     third party is a script that could, if that third party were ever
 *     compromised, act with those permissions. Pinning the version in
 *     package-lock.json and serving it ourselves removes that path entirely.
 *  2. The editor keeps working during a CDN outage, and on a slow or
 *     intermittent connection it comes from the same origin as the site.
 *  3. `npm run dev` works with no internet at all.
 *
 * Source maps are skipped: they are ~16 MB and are of no use in production.
 *
 * Runs automatically before `dev` and `build`. The output is generated, so it
 * is git-ignored — nothing vendored is ever committed.
 */
import { cp, mkdir, rm, readdir, stat } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const from = join(root, 'node_modules', '@sveltia', 'cms', 'dist');
const to = join(root, 'public', 'admin', 'cms');

try {
  await stat(from);
} catch {
  console.error(
    '\n[vendor-cms] @sveltia/cms is not installed, so the editor at /admin will not load.\n' +
      '            Run `npm install` and try again.\n',
  );
  process.exit(1);
}

await rm(to, { recursive: true, force: true });
await mkdir(to, { recursive: true });

// Everything except source maps.
await cp(from, to, {
  recursive: true,
  filter: (src) => !src.endsWith('.map'),
});

const files = await readdir(to, { recursive: true });
console.log(`[vendor-cms] editor bundle ready — ${files.length} files in public/admin/cms/`);
