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
6. [Updating content](#updating-content) - **the editor at /admin. Start here.**
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

### GitHub Pages (recommended - free, and you are already on GitHub)

This site is configured for a GitHub Pages **user site**, so it serves from the
root with no sub-path. That is deliberate: it is the same configuration a custom
domain needs, so moving to your own domain later is adding one file, not
reconfiguring and re-testing every internal link.

**Move it into its own repository first.** Keeping a portfolio inside a
repository called `Student_registration` puts that word in the URL of the site
you send to employers.

```bash
# from the root of Student_registration
git subtree split --prefix=portfolio -b portfolio-only
```

Then create a **public** repository on GitHub named exactly
`tebitramson69-netizen.github.io` - the name must match your username, and that
is what makes it a user site - and push to it:

```bash
git push git@github.com:tebitramson69-netizen/tebitramson69-netizen.github.io.git portfolio-only:main
```

Copy `.github/workflows/deploy-portfolio.yml` into the new repository and change
`working-directory: portfolio` to `working-directory: .`, since the portfolio is
now the whole repository.

Finally, in the new repository: **Settings -> Pages -> Source -> GitHub Actions**.

Your site is then at `https://tebitramson69-netizen.github.io`, and the CMS
config already points at that repository.

### Custom domain

Buy a domain, point it at GitHub Pages, then:

1. Add a file `public/CNAME` containing just your domain, e.g. `ramsontitih.dev`.
2. Change `PUBLIC_SITE_URL` in the deploy workflow to `https://ramsontitih.dev`.
3. Update the `Sitemap:` line in `public/robots.txt` to match.
4. In `public/admin/config.yml`, change `site_url` and `display_url`, and add the
   domain to your auth worker's `ALLOWED_DOMAINS`.

There is no base path to remove - the site already serves from the root, which
is why this migration is four small edits rather than a reconfiguration.

### Netlify or Vercel

Point the project at the `portfolio` directory. Build command `npm run build`,
publish directory `dist`. Set `PUBLIC_SITE_URL` in the dashboard's environment
variables. Leave `PUBLIC_BASE_PATH` unset.

Netlify and Vercel both rebuild automatically on every CMS save, same as GitHub
Pages, because a save is a commit.

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

**You do not need to touch code to change anything on this site.**

Go to `https://your-site/admin`, sign in with GitHub, and edit in forms. Every
save is a real commit to your repository, which triggers a rebuild — your change
is live in about a minute. It works on your phone.

### What you can edit there

| Section in the editor | What it controls |
| --- | --- |
| **Your details → Profile & photo** | Name, job title, headline, intro, location, email, **your profile photograph**, **your CV PDF**, and the availability badge |
| **Your details → Links** | GitHub, LinkedIn, X, personal site. **Leave a web address empty and that link disappears from the whole site**; fill it in and it appears in the header, footer and contact page at once |
| **Your details → Skills** | Skill groups and the one-line description under each skill |
| **Your details → Experience & education** | Jobs, internships, schools — add, reorder, delete |
| **Projects** | Add a new case study, upload screenshots, mark one as featured, reorder |

### Uploading your photo

Editor → **Your details → Profile & photo** → click **Profile photograph** →
upload. That is the whole process. Until you do, the site shows a designed
monogram rather than a broken image.

**You do not need to resize it first.** Upload the photo straight off your
phone. The build resizes it, converts it to WebP and generates a responsive
srcset automatically — a 3.2 MB photo was tested and came out at **14 KB** on a
phone-width screen and 67 KB at the largest size used.

This is why uploads go to `src/assets/uploads/` and not `public/`: anything in
`public/` is copied out byte for byte and would be served at full size. Do not
move the media folder.

### Adding a project

Editor → **Projects → New project**. Fill the fields, write the case study in
the Markdown box, save. The card, the case-study page, the sitemap entry and the
"next project" link all appear on their own.

### Turning on the editor

The editor needs permission to write to your repository. There are two ways to
give it that. **Start with the first one** - it takes about two minutes and
needs nothing but GitHub.

#### Option A: a personal access token (simplest)

1. Go to <https://github.com/settings/personal-access-tokens/new>
   (**Fine-grained tokens**).
2. Set:

   | Field | Value |
   | --- | --- |
   | Token name | `Portfolio editor` |
   | Expiration | 90 days, or whatever you are comfortable re-doing |
   | Repository access | **Only select repositories** -> pick your portfolio repo |
   | Permissions -> Repository -> **Contents** | **Read and write** |

3. Generate it and copy the token.
4. Open `https://your-site/admin`, click **Sign In Using Access Token**, paste.

Use a **fine-grained** token scoped to that one repository, not a classic
token. A classic token can touch every repository you own; this one can only
write to your portfolio, which is all the editor needs.

The token is stored in your browser on that device only. It never enters this
repository and is never sent anywhere except GitHub. Sign out, or delete the
token on GitHub, and access is gone immediately.

#### Option B: GitHub OAuth (nicer day to day, more setup)

With OAuth you click **Sign In with GitHub** and never handle a token or an
expiry date. The cost is a one-time setup of roughly ten minutes, because
GitHub will not let a browser complete a login on its own - a small relay has
to exchange the code for a token.

**1. Register a GitHub OAuth app**

<https://github.com/settings/developers> -> **OAuth Apps** -> **New OAuth App**.

| Field | Value |
| --- | --- |
| Application name | `Portfolio editor` |
| Homepage URL | `https://tebitramson69-netizen.github.io` |
| Authorization callback URL | leave it - you fill this in at step 3 |

Register it, then **generate a client secret**. Keep the Client ID and secret
open in a tab. **That secret is a real credential: it never goes in this
repository, in `config.yml`, or anywhere a browser can read it.**

**2. Deploy the auth relay**

Create a free account at <https://dash.cloudflare.com>, then deploy
[`sveltia-cms-auth`](https://github.com/sveltia/sveltia-cms-auth) - its README
has a one-click path. In the worker's **Settings -> Variables**, add:

| Variable | Value |
| --- | --- |
| `GITHUB_CLIENT_ID` | from step 1 |
| `GITHUB_CLIENT_SECRET` | from step 1 - mark it **encrypted** |
| `ALLOWED_DOMAINS` | `tebitramson69-netizen.github.io` |

Copy the worker address, e.g. `https://sveltia-cms-auth.yourname.workers.dev`.

**3. Point GitHub back at the relay**

In your OAuth app, set **Authorization callback URL** to
`https://YOUR-WORKER.workers.dev/callback`.

**4. Tell the editor where the relay is**

In `public/admin/config.yml`, uncomment `base_url:` and put your worker address
there. Commit and push. **Sign In with GitHub** now works.

### Editing without any of that

While you are developing locally, the editor needs no login at all:

```bash
npm run dev
```

Then open <http://localhost:4321/admin>. It reads and writes the files on your
own disk directly (Chrome or Edge — it uses the File System Access API). This is
what `local_backend: true` in the config enables, and it only ever applies on
localhost.

### Editing the files by hand

You can always skip the editor. The content is plain files:

```
src/content/settings/profile.json     you, your photo, your CV
src/content/settings/social.json      your links
src/content/settings/skills.json      your skills
src/content/settings/resume.json      experience and education
src/content/projects/*.md             one file per case study
```

`src/data/site.ts` reads and **validates** those files. Clear a required field
and `npm run build` fails with the file name and the exact field, instead of the
site quietly rendering an empty hero.

## Things you still need to supply

All of these are done in the editor at `/admin`. None of them require code.

| # | What | Where in the editor |
| --- | --- | --- |
| 1 | **A professional photograph.** Head-and-shoulders, plain or softly blurred background, good even light, looking at the camera, nobody else in frame. Portrait shape. A phone camera in daylight near a window is completely fine - and you do **not** need to resize or compress it first. | Your details -> Profile & photo -> *Profile photograph* |
| 2 | **Your LinkedIn URL.** No profile could be verified when this site was built, so the link is hidden rather than pointing somewhere invented. | Your details -> Links -> LinkedIn -> *Web address* |
| 3 | **Your CV as a PDF.** No CV was found in any connected source. The Download CV button appears the moment you upload one. | Your details -> Profile & photo -> *CV / resume* |
| 4 | **What you actually did at NgahTech Group.** The placement is verified; the work you did there is not something anyone else can write for you. Name the projects, the stack, and one thing you shipped. | Your details -> Experience & education -> *Software Engineering Intern* -> *What the role was* |
| 5 | **Confirm your study dates.** `2025 - Present` was inferred from coursework records dated October 2025. Correct it if wrong. | Your details -> Experience & education -> Education -> *Dates* |
| 6 | **Project screenshots.** All three projects currently show a designed placeholder. | Projects -> pick a project -> *Screenshot* |

None of these render as a visible "unfinished" notice to visitors. A missing
photo shows a designed monogram, a missing CV shows no button, a missing link
simply is not there. The site looks finished at every stage - these are
improvements, not gaps a recruiter will spot.

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

### The editor at /admin

The CMS is the one part of this project that touches a credential, so it is
worth being precise about where that credential lives.

- **Your GitHub OAuth client secret is never in this repository.** It lives only
  in the Cloudflare worker's encrypted environment variables. `config.yml`
  contains a public client-side redirect URL and nothing more.
- `/admin` is a **login gate, not a hidden page**. It is safe that anyone can
  load it: without a GitHub account that has write access to the repository,
  signing in gets them nothing. Security comes from GitHub's permissions, not
  from the URL being secret.
- It is marked `noindex, nofollow` and disallowed in `robots.txt`, so it stays
  out of search results.
- The worker's `ALLOWED_DOMAINS` restricts which sites may complete a login, so
  someone copying your config onto another domain cannot use your OAuth app.
- Every save is an ordinary commit by you. Nothing can change your site without
  appearing in `git log`, and anything can be reverted.

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
|- astro.config.mjs          site URL, optional base path, sitemap
|- .env.example              every variable, documented, no secrets
|- scripts/audit.mjs         the browser audit described above
|- public/
|  |- admin/                 <-- THE EDITOR
|  |  |- index.html          loads Sveltia CMS
|  |  \- config.yml          which fields you see, and where they are saved
|  \- favicon.svg, og-default.svg, robots.txt
\- src/
   |- assets/uploads/        <-- your uploaded photos and screenshots land here
   |                            (in src/, not public/, so the build optimises them)
   |- content/
   |  |- settings/*.json     <-- everything about you, written by the editor
   |  \- projects/*.md       <-- one case study per file
   |- content.config.ts      schema every project file is validated against
   |- data/site.ts           reads and VALIDATES the JSON above; do not edit
   |- lib/
   |  |- url.ts              base-path-safe URL helper
   |  \- images.ts           resolves CMS image paths into optimised assets
   |- styles/
   |  |- tokens.css          the entire design system
   |  \- global.css          reset, typography, layout, motion, print
   |- components/            BaseHead, Header, Footer, ThemeToggle, ProjectCard,
   |                         Timeline, Portrait, ContactForm, SectionHeading, Icon
   |- layouts/Base.astro     the shell every page uses
   \- pages/                 index, about, projects/, resume, contact, 404
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
