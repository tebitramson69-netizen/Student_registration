/**
 * Build a site-root-relative URL that respects `base` when the site is served
 * from a sub-path (a GitHub Pages project site, for example).
 *
 * Always use this for internal links and for assets under `public/`; a bare
 * "/about" breaks the moment the site moves to a sub-path.
 */
export function url(path: string): string {
  const base = import.meta.env.BASE_URL || '/';
  const clean = path.replace(/^\/+/, '');
  const prefix = base.endsWith('/') ? base : `${base}/`;
  return clean === '' ? prefix : `${prefix}${clean}`;
}

/** True when `current` is the page (or a child of the page) at `href`. */
export function isCurrent(currentPath: string, href: string): boolean {
  const norm = (p: string) => {
    const stripped = p.replace(import.meta.env.BASE_URL || '/', '/').replace(/\/+$/, '');
    return stripped === '' ? '/' : stripped;
  };
  const a = norm(currentPath);
  const b = norm(href);
  return b === '/' ? a === '/' : a === b || a.startsWith(`${b}/`);
}
