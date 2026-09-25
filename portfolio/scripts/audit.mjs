import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

/**
 * Browser audit: responsive overflow, touch targets, heading hierarchy,
 * keyboard navigation, theme switching, console errors, and screenshots.
 *
 *   npm run build && npm run preview     # one terminal
 *   node scripts/audit.mjs               # another
 *
 * Override with env vars:
 *   AUDIT_BASE=http://127.0.0.1:4321   the preview server
 *   AUDIT_SHOTS=./.audit               where screenshots are written
 *   CHROMIUM_PATH=/path/to/chrome      unset to use Playwright's own browser
 */
const BASE = process.env.AUDIT_BASE || 'http://127.0.0.1:4321';
const SHOTS = process.env.AUDIT_SHOTS || new URL('../.audit', import.meta.url).pathname;

const pages = ['/', '/about', '/projects', '/projects/school-management-system', '/resume', '/contact', '/404'];
const widths = [320, 375, 390, 412, 768, 1024, 1280, 1440, 1920];

await mkdir(SHOTS, { recursive: true });

const browser = await chromium.launch(
  process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {},
);
const problems = [];

// --- 1. Horizontal overflow + console errors across every page and width ---
for (const w of widths) {
  const ctx = await browser.newContext({ viewport: { width: w, height: 900 }, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  page.on('console', (m) => { if (m.type() === 'error' && !m.text().includes('ERR_CERT_AUTHORITY_INVALID')) problems.push(`console@${w} ${page.url()}: ${m.text()}`); });
  page.on('pageerror', (e) => problems.push(`pageerror@${w}: ${e.message}`));

  for (const p of pages) {
    const res = await page.goto(BASE + p, { waitUntil: 'networkidle' });
    if (!res || (res.status() >= 400 && p !== '/404')) problems.push(`status ${res?.status()} on ${p}@${w}`);

    const overflow = await page.evaluate(() => {
      const de = document.documentElement;
      const offenders = [];
      if (de.scrollWidth > de.clientWidth + 1) {
        for (const el of document.querySelectorAll('body *')) {
          const r = el.getBoundingClientRect();
          if (r.right > de.clientWidth + 1 || r.left < -1) {
            offenders.push(`${el.tagName}.${(el.className && typeof el.className === 'string' ? el.className.split(' ')[0] : '')} right=${Math.round(r.right)}`);
          }
        }
      }
      return { scrollW: de.scrollWidth, clientW: de.clientWidth, offenders: offenders.slice(0, 5) };
    });
    if (overflow.scrollW > overflow.clientW + 1) {
      problems.push(`OVERFLOW ${p}@${w}: ${overflow.scrollW}>${overflow.clientW} :: ${overflow.offenders.join(' | ')}`);
    }

    // Touch targets on mobile widths
    if (w <= 412) {
      const small = await page.evaluate(() => {
        const out = [];
        for (const el of document.querySelectorAll('a, button, input, textarea')) {
          const r = el.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) continue;
          if (r.height < 24) out.push(`${el.tagName}:${(el.textContent || '').trim().slice(0, 25)} h=${Math.round(r.height)}`);
        }
        return out.slice(0, 6);
      });
      if (small.length) problems.push(`SMALL-TARGET ${p}@${w}: ${small.join(' | ')}`);
    }
  }
  await ctx.close();
}

// --- 2. Heading hierarchy, alt text, labels, single h1 ---
{
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  for (const p of pages) {
    await page.goto(BASE + p, { waitUntil: 'networkidle' });
    const a11y = await page.evaluate(() => {
      const hs = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].map((h) => +h.tagName[1]);
      const skips = [];
      for (let i = 1; i < hs.length; i++) if (hs[i] - hs[i - 1] > 1) skips.push(`${hs[i - 1]}->${hs[i]}`);
      const imgs = [...document.querySelectorAll('img')].filter((i) => i.getAttribute('alt') === null).length;
      const unlabelled = [...document.querySelectorAll('input:not([type=hidden]), textarea, select')].filter((el) => {
        const id = el.id;
        return !(id && document.querySelector(`label[for="${id}"]`)) && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby');
      }).length;
      const namelessLinks = [...document.querySelectorAll('a')].filter(
        (a) => !(a.textContent || '').trim() && !a.getAttribute('aria-label') && !a.querySelector('[class*=visually-hidden]') && a.getAttribute('aria-hidden') !== 'true'
      ).length;
      return { h1: document.querySelectorAll('h1').length, skips, imgs, unlabelled, namelessLinks, lang: document.documentElement.lang, title: document.title, desc: document.querySelector('meta[name=description]')?.getAttribute('content')?.length || 0 };
    });
    if (a11y.h1 !== 1) problems.push(`H1-COUNT ${p}: ${a11y.h1}`);
    if (a11y.skips.length) problems.push(`HEADING-SKIP ${p}: ${a11y.skips.join(',')}`);
    if (a11y.imgs) problems.push(`IMG-NO-ALT ${p}: ${a11y.imgs}`);
    if (a11y.unlabelled) problems.push(`UNLABELLED-INPUT ${p}: ${a11y.unlabelled}`);
    if (a11y.namelessLinks) problems.push(`NAMELESS-LINK ${p}: ${a11y.namelessLinks}`);
    if (!a11y.lang) problems.push(`NO-LANG ${p}`);
    if (a11y.desc < 50 || a11y.desc > 175) problems.push(`META-DESC-LEN ${p}: ${a11y.desc}`);
  }
  await ctx.close();
}

// --- 3. Keyboard navigation: skip link, focus visibility, mobile menu ---
{
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  await page.keyboard.press('Tab');
  const first = await page.evaluate(() => document.activeElement?.className || document.activeElement?.tagName);
  if (!String(first).includes('skip-link')) problems.push(`SKIP-LINK not first tab stop (got ${first})`);

  // Walk 25 tab stops and confirm each has a visible outline
  let noOutline = 0;
  for (let i = 0; i < 25; i++) {
    await page.keyboard.press('Tab');
    const ok = await page.evaluate(() => {
      const el = document.activeElement;
      if (!el || el === document.body) return true;
      const s = getComputedStyle(el);
      return s.outlineStyle !== 'none' || s.boxShadow !== 'none';
    });
    if (!ok) noOutline++;
  }
  if (noOutline) problems.push(`FOCUS-INVISIBLE on ${noOutline} of 25 tab stops`);
  await ctx.close();
}

// --- 4. Mobile menu behaviour ---
{
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const page = await ctx.newPage();
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  const toggle = page.locator('#nav-toggle');
  if (!(await toggle.isVisible())) problems.push('MOBILE-MENU toggle not visible at 390px');
  await toggle.click();
  await page.waitForTimeout(350);
  const navVisible = await page.locator('#site-nav a', { hasText: 'Projects' }).first().isVisible();
  if (!navVisible) problems.push('MOBILE-MENU did not open');
  const expanded = await toggle.getAttribute('aria-expanded');
  if (expanded !== 'true') problems.push(`MOBILE-MENU aria-expanded=${expanded}`);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(350);
  if ((await toggle.getAttribute('aria-expanded')) !== 'false') problems.push('MOBILE-MENU Escape did not close');
  await ctx.close();
}

// --- 5. Dark mode: toggle, persistence, and a real repaint ---
{
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, colorScheme: 'light' });
  const page = await ctx.newPage();
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  const lightBg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  await page.click('#theme-toggle');
  await page.waitForTimeout(250);
  const darkBg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
  if (lightBg === darkBg) problems.push(`THEME toggle did not change background (${lightBg})`);
  await page.reload({ waitUntil: 'networkidle' });
  const afterReload = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
  if (afterReload !== 'dark') problems.push(`THEME not persisted after reload (${afterReload})`);
  await ctx.close();
}

// --- 6. Screenshots ---
const shots = [
  ['home-desktop', '/', 1440, 'light'],
  ['home-desktop-dark', '/', 1440, 'dark'],
  ['home-mobile', '/', 390, 'light'],
  ['projects-desktop', '/projects', 1440, 'light'],
  ['case-desktop', '/projects/school-management-system', 1440, 'light'],
  ['case-mobile', '/projects/school-management-system', 390, 'light'],
  ['resume-desktop', '/resume', 1440, 'dark'],
  ['contact-mobile', '/contact', 390, 'light'],
  ['home-320', '/', 320, 'light'],
];
for (const [name, path, width, scheme] of shots) {
  const ctx = await browser.newContext({ viewport: { width, height: 1000 }, colorScheme: scheme, deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  await page.goto(BASE + path, { waitUntil: 'networkidle' });
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.waitForTimeout(700);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true });
  await ctx.close();
}

await browser.close();

if (problems.length) {
  console.error('PROBLEMS:\n' + problems.join('\n'));
  process.exitCode = 1;
} else {
  console.log(`ALL CHECKS PASSED — screenshots in ${SHOTS}`);
}
