# Tebit Ramson Titih — Portfolio

The personal portfolio of **Tebit Ramson Titih**, HND Software Engineering
student at Saint Louis University Institute, Douala, Cameroon.

A static site: no server, no database, no runtime secrets. It builds to plain
HTML, CSS and a little JavaScript that you can host on GitHub Pages, Netlify,
Vercel, cPanel shared hosting, or an Apache/XAMPP `htdocs` folder.

---

## Table of contents

1. [Stack, and why](#stack-and-why)
2. [Install](#install)
3. [Develop](#develop)
4. [Build](#build)
5. [Deploy](#deploy)
6. [Updating content](#updating-content) ← **start here for day-to-day edits**
7. [Things you still need to supply](#things-you-still-need-to-supply)
8. [Contact form](#contact-form)
9. [Analytics](#analytics)
10. [SEO](#seo)
11. [Accessibility](#accessibility)
12. [Performance](#performance)
13. [Security](#security)
14. [Testing](#testing)
15. [Project structure](#project-structure)
16. [Sources used, and what was deliberately left out](#sources-used-and-what-was-deliberately-left-out)

---

## Stack, and why

| Choice | Reason |
| --- | --- |
| **Astro 5** | Ships zero JavaScript by default and renders everything to static HTML at build time. A portfolio is a document, not an app — this is the shape that matches. |
| **Plain CSS with custom properties** | No Tailwind, no build-time CSS framework. The whole design system is ~200 lines of tokens you can read in one sitting, and it is the same CSS you already write. |
| **TypeScript** | Only in the data and helper files, where a typo in a project's front-matter should fail the build rather than render an empty section. |
| **Markdown content collections** | Case studies are Markdown files with a schema. Content is separate from presentation, and a malformed entry breaks the build loudly. |
| **No backend** | Nothing to keep patched, nothing to pay for, nothing that can leak. The contact form posts to a configurable third-party relay. |

Astro was chosen over Next.js deliberately: this site has no interactive
application behind it, and Next.js would add a React runtime, a heavier build,
and a hosting story that a static host cannot fully satisfy. The output of
`npm run build` here is a folder of `.html` files — you can open one in a
browser with no server at all.

---

## Install

Requires **Node.js 20 or newer** (Node 22 recommended) and npm.

```bash
cd portfolio
npm install
cp .env.example .env     # optional — the site builds fine without it
```

---

## Develop

```bash
npm run dev
```

Opens on <http://localhost:4321> with hot reload. Edits to
`src/data/site.ts` or any file in `src/content/projects/` appear immediately.

---

## Build

```bash
npm run build      # type-checks, then builds into dist/
npm run preview    # serves dist/ exactly as production will
```

`npm run build` runs `astro check` first, so a type error or a malformed project
front-matter fails the build rather than reaching production. Use
`npm run build:fast` to skip the check when you are in a hurry.

The result is `dist/` — static files, nothing else.

---

## Deploy

### GitHub Pages (recommended — free, and you are already on GitHub)

A workflow is included at `../.github/workflows/deploy-portfolio.yml`. It builds
and publishes on every push to `main`, and can also be run by hand from the
Actions tab.

To turn it on:

1. Push this branch and merge it to `main`.
2. In the repository, go to **Settings → Pages** and set **Source** to
   **GitHub Actions**.
3. Run the workflow once from the **Actions** tab.

Because this is a *project* site, it will live at
`https://tebitramson69-netizen.github.io/Student_registration/`. The workflow
sets `PUBLIC_BASE_PATH=/Student_registration` for you.

### Custom domain

Buy a domain, point it at GitHub Pages, then:

1. Add a file `public/CNAME` containing just your domain, e.g. `ramsontitih.dev`.
2. Set `PUBLIC_SITE_URL` to `https://ramsontitih.dev`.
3. **Remove** `PUBLIC_BASE_PATH` (a custom domain serves from the root).

### Netlify or Vercel

Point the project at the `portfolio` directory. Build command `npm run build`,
publish directory `dist`. Set `PUBLIC_SITE_URL` in the dashboard's environment
variables. Leave `PUBLIC_BASE_PATH` unset.

### Apache / XAMPP / shared cPanel hosting

```bash
npm run build
```

Upload the **contents** of `dist/` into `htdocs/` or `public_html/`. No PHP, no
database, no configuration. Set `PUBLIC_SITE_URL` to the real domain before
building so the canonical tags and sitemap are correct.

### Splitting the portfolio into its own repository

This lives inside the `Student_registration` repository under `portfolio/`. When
you want it standalone — which you probably will, for a cleaner URL:

```bash
git subtree split --prefix=portfolio -b portfolio-only
# create an empty repo on GitHub, then:
git push git@github.com:tebitramson69-netizen/portfolio.git portfolio-only:main
```

This keeps the commit history rather than squashing it.

---

## Updating content

**Almost every change you will want to make is in one of two places.**

### 1. Facts about you — `src/data/site.ts`

| What | Where in the file |
| --- | --- |
| Name, role, tagline, location, email | `profile` |
| GitHub / LinkedIn / email links | `socials` |
| Skills, grouped | `skillGroups` |
| Jobs and internships | `experience` |
| Schools and qualifications | `education` |
| CV download button | `cv` |
| Profile photograph | `portrait` |
| Navigation menu | `nav` |

Every entry is commented. Adding a skill is adding one line to an array.

### 2. Projects — `src/content/projects/*.md`

Each project is one Markdown file. The front-matter block at the top is
validated against a schema in `src/content.config.ts`, so if you misspell a
field the build tells you exactly which file and which field.

```markdown
---
title: My New Project
summary: One sentence for the card.
role: Sole developer
category: Web application
order: 4            # lower numbers appear first
featured: false     # true puts it on the homepage
year: '2026'        # quotes required — YAML reads a bare 2026 as a number
status: In progress
tech:
  - PHP
  - MySQL
repo: https://github.com/you/repo    # optional
demo: https://example.com            # optional
metrics:                             # optional — VERIFIED numbers only
  - value: '12'
    label: Database tables
image: images/projects/my-project.png   # optional
imageAlt: Description of the screenshot # required if image is set
---

## The problem
...your case study, in normal Markdown...
```

Delete a file and the project disappears, including from the sitemap. No other
file needs touching.

### Adding project screenshots

1. Save the image under `public/images/projects/`.
2. Add `image:` and `imageAlt:` to that project's front-matter.

Until you do, the card shows a designed placeholder rather than a broken frame.
Use WebP or optimised PNG, roughly 1600×1000, and keep each under ~200 KB.

---

## Things you still need to supply

These are marked visibly on the live site so you cannot forget them.

| # | What | Where to put it |
| --- | --- | --- |
| 1 | **A professional photograph.** Head-and-shoulders, plain or softly blurred background, good even light, looking at the camera, no one else in frame. Roughly 1000×1250 (4:5 portrait). A phone camera in daylight near a window is completely fine. | Save as `public/images/ramson.jpg`, then set `portrait.available = true` in `src/data/site.ts`. |
| 2 | **Your LinkedIn URL.** No profile could be verified, so the contact page shows the entry greyed out and deliberately unlinked rather than guessing a URL. | `socials` in `src/data/site.ts` — set `href` and `handle`, then `verified: true`. |
| 3 | **Your CV as a PDF.** No CV was found in any connected source. | Save as `public/cv/tebit-ramson-titih-cv.pdf`, then set `cv.available = true`. |
| 4 | **What you actually did at NgahTech Group.** The placement itself is verified; the work you did there is not something anyone else can write for you. | `experience[0].summary` in `src/data/site.ts`. Delete the `todo` line once done. |
| 5 | **Confirm your study dates.** `2025 — Present` was inferred from coursework records dated October 2025. Correct it if wrong. | `education[0].period`. Delete the `todo` line once done. |
| 6 | **Project screenshots.** Three placeholders are showing. | See [Adding project screenshots](#adding-project-screenshots). |

Each `todo:` note renders as a visible dashed box on the site. Remove the line
from the data file and the box disappears.

---

## Contact form

There is no server here, so the form posts to a third-party form relay. Pick
one — [Formspree](https://formspree.io), [Basin](https://usebasin.com) and
[Web3Forms](https://web3forms.com) all have free tiers — create an endpoint, and
set it:

```dotenv
PUBLIC_CONTACT_ENDPOINT=https://formspree.io/f/your-form-id
```

**Leave it empty and the contact page shows a direct email link instead.** That
is the deliberate default: a form that silently discards messages is worse than
no form.

That endpoint URL is public by nature — it is a write-only intake address, not a
credential. Nothing secret is ever shipped to the browser.

Protections in place:

- **Honeypot field** (`_gotcha`), positioned off-screen rather than
  `display: none`, since some bots skip hidden inputs. If it is filled, the form
  reports success and sends nothing.
- **Length caps** on every field, so a single submission cannot be enormous.
- **Client-side validation** for fast, clear errors — with the relay validating
  again, because client-side validation is a courtesy and never a control.
- **Errors are never shown raw.** A failed submission tells the visitor to email
  directly instead of printing a status code at them.

### Why not wire it to Gmail directly?

Because it cannot be done safely from a static site. Sending mail through the
Gmail API needs an OAuth client secret or a service-account key, and anything a
static page can reach, a visitor can read. That would publish the credential to
the internet. A relay endpoint is the correct answer for a site with no backend.
If you later want mail sent from your own address, add a small serverless
function (Netlify/Vercel) that holds the credential server-side and point
`PUBLIC_CONTACT_ENDPOINT` at it.

---

## Analytics

None by default. Zero third-party requests, zero cookies, nothing to disclose.

To add privacy-friendly, cookieless analytics (Plausible or Umami):

```dotenv
PUBLIC_ANALYTICS_SRC=https://plausible.io/js/script.js
PUBLIC_ANALYTICS_DOMAIN=ramsontitih.dev
```

Leave them empty and no script tag is emitted at all.

---

## SEO

- Unique `<title>` and meta description on every page, each inside the ~155–165
  character window search engines actually display.
- Canonical URLs on every page.
- Open Graph and Twitter/X card tags, with a designed 1200×630 preview image
  (`public/og-default.svg`).
- `sitemap-index.xml` generated at build time by `@astrojs/sitemap`.
- `robots.txt` allowing everything and pointing at the sitemap.
- **Structured data**: `Person` schema on the homepage, `CreativeWork` on each
  case study.
- Semantic HTML with exactly one `<h1>` per page and no skipped heading levels —
  both enforced by the audit script.
- Descriptive URLs (`/projects/school-management-system`).

Content is written for people. There is no keyword stuffing, and the case
studies say what is *not* finished as readily as what is.

---

## Accessibility

Targeting **WCAG 2.2 AA**. Implemented and verified:

- Semantic landmarks, one `<h1>` per page, no skipped heading levels.
- A skip link that is the first tab stop on every page.
- Visible focus rings on every interactive element — never removed, verified
  across 25 consecutive tab stops.
- Full keyboard operation: the mobile menu opens by keyboard, closes on
  <kbd>Esc</kbd>, and returns focus to its trigger.
- All form fields have real `<label for>` associations, inline errors tied by
  `aria-describedby`, and `aria-invalid` on failure.
- Submission status announced via `role="status"` / `aria-live="polite"`.
- Icon-only and ambiguous links carry visually-hidden text ("opens in a new
  tab", "Source for *project name*").
- Decorative SVGs are `aria-hidden`; meaningful images have real alt text.
- **Touch targets are at least 24×24 CSS px** (SC 2.5.8), verified at 320–412px.
- `prefers-reduced-motion: reduce` disables every transition, animation and
  smooth scroll — and the reveal observer is not created at all.
- Content is fully visible without JavaScript; the reveal animation can only
  add polish, never withhold text.
- Colour is never the only carrier of meaning.

---

## Performance

- **Zero client JavaScript by default.** The only scripts are the theme toggle,
  the mobile menu, the reveal observer and the contact form — roughly 2 KB
  gzipped in total.
- Static HTML, so there is no hydration and no runtime.
- Fonts: two families, `display=swap`, with `preconnect` — text paints
  immediately rather than waiting.
- Theme resolution runs inline before first paint, so dark-mode visitors never
  see a white flash.
- Images below the fold are `loading="lazy"` with `decoding="async"`, and every
  image declares `width`/`height` so nothing shifts as it loads.
- Brand assets are SVG — the favicon is 359 bytes, the social preview 1.4 KB.
- Link prefetch on hover, so a click into a case study is already loading.

---

## Security

- **No secrets anywhere.** There is no server, no database and no API key. Only
  `PUBLIC_*` variables exist and they are public by definition.
- `.env` is git-ignored; `.env.example` contains placeholder names only.
- Every external link carries `rel="noopener noreferrer"`.
- No third-party scripts unless *you* opt in to analytics.
- No cookies. `localStorage` holds exactly one value — your theme choice — and
  every read and write is wrapped in `try/catch` so a private window or blocked
  storage cannot break the page.
- Connected services (Gmail, Google Drive, Notion, GitHub) were used **only to
  research content during the build**. The published site requires access to
  none of them, and nothing private was copied into it.

---

## Testing

```bash
npm run build && npm run preview     # in one terminal
node scripts/audit.mjs               # in another
```

`scripts/audit.mjs` drives a real Chromium browser and checks:

- **Horizontal overflow** on 7 pages × 9 viewport widths (320, 375, 390, 412,
  768, 1024, 1280, 1440, 1920), naming the offending element if it finds any.
- **Touch-target sizes** at every mobile width.
- **Heading hierarchy** — one `<h1>`, no skipped levels.
- Missing `alt`, unlabelled form fields, links with no accessible name, missing
  `lang`, and meta-description length.
- **Keyboard navigation** — skip link first, focus visible on 25 tab stops.
- **Mobile menu** — opens, sets `aria-expanded`, closes on <kbd>Esc</kbd>.
- **Dark mode** — toggles, repaints, and survives a reload.
- Console and page errors on every page at every width.
- Writes screenshots for visual review.

It exits printing either a list of problems or `ALL CHECKS PASSED`. The current
state is **ALL CHECKS PASSED**.

On a machine where Playwright's own browsers are installed, drop the
`CHROMIUM_PATH` override at the top of the file, or set it to your binary.

---

## Project structure

```
portfolio/
├── astro.config.mjs        site URL, base path, sitemap
├── .env.example            every variable, documented, no secrets
├── scripts/audit.mjs       the browser audit described above
├── public/                 copied verbatim: favicon, OG image, robots.txt
│   └── images/             ← your photo and project screenshots go here
└── src/
    ├── content.config.ts   schema every project file is validated against
    ├── content/projects/   the case studies — one Markdown file each
    ├── data/site.ts        ← every fact about you
    ├── lib/url.ts          base-path-safe URL helper
    ├── styles/
    │   ├── tokens.css      the entire design system
    │   └── global.css      reset, typography, layout, motion, print
    ├── components/         BaseHead, Header, Footer, ThemeToggle, ProjectCard,
    │                       Timeline, Portrait, ContactForm, SectionHeading, Icon
    ├── layouts/Base.astro  the shell every page uses
    └── pages/              index, about, projects/, resume, contact, 404
```

### Design system

Everything visual resolves to a token in `src/styles/tokens.css`. Changing the
accent colour is changing three variables. The type scale is fluid — it
interpolates between 320px and 1280px rather than jumping at breakpoints — so
there is no viewport where text is awkwardly sized.

Light and dark are the *same* design with different token values, not two
designs. The theme resolves from the OS preference, can be overridden by the
toggle, and persists in `localStorage`.

---

## Sources used, and what was deliberately left out

Connected services were used as research, not as a publishing pipeline.

**Used:**

- **GitHub** — the three public repositories (`School-Management-System`,
  `ai-script-to-video-studio`, `Student_registration`), their source, READMEs,
  schemas, progress notes and commit history. Every technical claim and every
  number on this site comes from reading that code.
- **Google Drive** — one shared document confirming the NgahTech Group
  internship placement, and a coursework record dated October 2025 used to
  estimate the start of study.
- **Your own stated working stack**, from the brief.

**Deliberately excluded:**

- **The rest of that internship placement document** — it lists other students
  by name with their placements. Only your own row was used.
- **Academic records** — a shared attendance and quiz-mark document was found
  and not used. Grades are private.
- **Gmail contents** — searched for a CV, certificates and internship
  correspondence. None were found; everything else in the mailbox is newsletters
  and personal mail and none of it was published.
- **Drive photographs** — the only images available are casual event photos
  taken on one day in August 2026, several of which show other people. None is a
  professional portrait, so none was used and no face was generated to fill the
  gap. See [Things you still need to supply](#things-you-still-need-to-supply).
- **Notion and Slack** — searched, empty or nothing relevant.
- **Any invented fact** — no fabricated metrics, testimonials, clients,
  employers, certifications or dates appear anywhere on this site.

---

## Licence

Source code: do as you like with it. The written content, case studies and
personal information are © Tebit Ramson Titih.
