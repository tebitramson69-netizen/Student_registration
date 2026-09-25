import type { ImageMetadata } from 'astro';

/**
 * Resolve an image path written by the CMS into an Astro image asset.
 *
 * Uploads land in `src/assets/uploads/` rather than `public/` on purpose.
 * Anything in `public/` is copied to the output byte for byte — so a 3 MB
 * photo straight off a phone would be served to every visitor at 3 MB. From
 * `src/assets/` Astro resizes it, converts it to a modern format and emits a
 * srcset at build time, with no thought required from whoever uploaded it.
 *
 * Returns `null` when the file is missing (renamed, deleted, or a path typed
 * by hand). Callers fall back to their placeholder rather than failing the
 * build, because a missing screenshot should never take the site down.
 */
const uploads = import.meta.glob<{ default: ImageMetadata }>(
  '/src/assets/uploads/**/*.{jpeg,jpg,png,webp,avif,gif,svg}',
  { eager: true },
);

export function uploaded(path: string | undefined | null): ImageMetadata | null {
  if (!path) return null;
  const trimmed = path.trim();
  if (trimmed === '') return null;
  const key = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
  return uploads[key]?.default ?? null;
}
