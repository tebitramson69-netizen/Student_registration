/**
 * Single source of truth for everything about the person behind this site.
 *
 * Presentation lives in `src/components`; facts live here. To update the site,
 * edit this file (and `src/content/projects/*.md` for the case studies) — you
 * should never need to touch a component to change a fact.
 *
 * RULE OF THIS FILE: every claim must be verifiable. Anything that cannot be
 * verified is either omitted or marked with `PLACEHOLDER` so it is obvious both
 * here and in the rendered page.
 */

export interface SocialLink {
  label: string;
  href: string;
  /** Icon key resolved by `src/components/Icon.astro`. */
  icon: 'github' | 'linkedin' | 'mail' | 'location';
  /** Short text shown next to the link on the contact page. */
  handle: string;
  /** False when the URL is a placeholder the owner still has to supply. */
  verified: boolean;
}

export const profile = {
  name: 'Tebit Ramson Titih',
  /** Used for the wordmark and the avatar monogram. */
  initials: 'TR',
  shortName: 'Ramson',
  role: 'Software Engineering Student & Full-Stack Developer',
  /** One line, shown under the name in the hero. Must fit on two lines at 320px. */
  tagline:
    'I build complete web systems in PHP and MySQL — and I build them for the way things actually work in Cameroon.',
  location: 'Douala, Cameroon',
  email: 'tebitramsontitih@gmail.com',
  /** Meta description fallback and structured-data description. */
  summary:
    'HND Software Engineering student in Douala, Cameroon, building real web systems with PHP, MySQL and Laravel — including a school platform built for Cameroonian grading.',
} as const;

export const socials: SocialLink[] = [
  {
    label: 'GitHub',
    href: 'https://github.com/tebitramson69-netizen',
    icon: 'github',
    handle: 'tebitramson69-netizen',
    verified: true,
  },
  {
    label: 'Email',
    href: `mailto:${profile.email}`,
    icon: 'mail',
    handle: profile.email,
    verified: true,
  },
  {
    // PLACEHOLDER — no LinkedIn profile URL could be verified from the sources
    // available when this site was built. Replace `href` and `handle` with the
    // real profile, or delete this entry entirely; the UI handles both.
    label: 'LinkedIn',
    href: '#',
    icon: 'linkedin',
    handle: 'Add your LinkedIn URL',
    verified: false,
  },
];

/** Only the links that actually point somewhere. Used in the header/footer. */
export const verifiedSocials = socials.filter((s) => s.verified);

/* -------------------------------------------------------------------------
   Skills
   Grouped by role in a system rather than by marketing category, and kept
   deliberately short. Everything listed here is backed either by a public
   repository or by the owner's own stated working stack.
   ------------------------------------------------------------------------- */
export interface SkillGroup {
  title: string;
  note: string;
  items: { name: string; detail: string }[];
}

export const skillGroups: SkillGroup[] = [
  {
    title: 'Backend',
    note: 'Where most of my work happens.',
    items: [
      { name: 'PHP 8', detail: 'Custom MVC and Laravel — my primary language' },
      { name: 'Laravel', detail: 'Queues, migrations, policies, Eloquent' },
      { name: 'Session auth & RBAC', detail: 'Role-based dashboards and route guards' },
      { name: 'Composer', detail: 'Dependency and autoload management' },
    ],
  },
  {
    title: 'Databases',
    note: 'Schema design first, queries second.',
    items: [
      { name: 'MySQL / MariaDB', detail: 'Normalised relational schemas' },
      { name: 'SQL', detail: 'Joins, aggregates, constraints, indexes' },
      { name: 'PDO', detail: 'Prepared statements and transactions' },
      { name: 'Migrations', detail: 'Versioned, repeatable schema changes' },
    ],
  },
  {
    title: 'Frontend',
    note: 'No framework unless the problem asks for one.',
    items: [
      { name: 'HTML', detail: 'Semantic, accessible document structure' },
      { name: 'CSS', detail: 'Responsive layouts, custom properties, dark mode' },
      { name: 'JavaScript', detail: 'Vanilla DOM work and progressive enhancement' },
      { name: 'Bootstrap', detail: 'When a project needs speed over bespoke design' },
    ],
  },
  {
    title: 'Practices & tooling',
    note: 'How the work gets built and kept honest.',
    items: [
      { name: 'Git & GitHub', detail: 'Branching, reviewable history, CI workflows' },
      { name: 'Application security', detail: 'CSRF, session hardening, SQL injection, XSS' },
      { name: 'PHPUnit', detail: 'Automated test suites against real behaviour' },
      { name: 'XAMPP / Apache', detail: 'Local and shared-host deployment' },
      { name: 'VS Code', detail: 'Daily development environment' },
      { name: 'AI-assisted development', detail: 'As a reviewer and pair, not as an author' },
    ],
  },
];

/* -------------------------------------------------------------------------
   Experience
   ------------------------------------------------------------------------- */
export interface TimelineEntry {
  title: string;
  organisation: string;
  location: string;
  period: string;
  /** Leading paragraph. Keep to what is true. */
  summary: string;
  /** Optional supporting points. Omit rather than invent. */
  points?: string[];
  tags?: string[];
  /** Rendered as a small "needs your input" note. Remove once filled in. */
  todo?: string;
}

export const experience: TimelineEntry[] = [
  {
    title: 'Software Engineering Intern',
    organisation: 'NgahTech Group',
    location: 'Simbock, Yaoundé, Cameroon',
    period: '2026',
    summary:
      'Industrial internship placement for my HND programme, working inside a Cameroonian software company rather than on coursework — my first experience of building software to someone else’s requirements, deadlines and review.',
    todo:
      'Replace this summary with the work you actually did at NgahTech Group — the projects, the stack, and one thing you shipped. See src/data/site.ts.',
  },
  {
    title: 'Independent Project Work',
    organisation: 'Self-directed',
    location: 'Douala, Cameroon',
    period: '2025 — Present',
    summary:
      'Everything on this site outside the internship was designed, built and maintained on my own initiative — choosing the problem, the schema, the architecture and the security model, then living with those decisions long enough to find out which ones were wrong.',
    points: [
      'Three public repositories spanning a custom PHP MVC framework, a Laravel application and a foundational CRUD system.',
      'Roughly 33,000 lines of PHP written and reviewed across the two larger projects.',
      'Security work done deliberately rather than as an afterthought: CSRF tokens, session hardening, login rate limiting and credential externalisation.',
    ],
    tags: ['PHP', 'Laravel', 'MySQL', 'Git'],
  },
];

/* -------------------------------------------------------------------------
   Education
   ------------------------------------------------------------------------- */
export const education: TimelineEntry[] = [
  {
    title: 'HND, Software Engineering',
    organisation: 'Saint Louis University Institute',
    location: 'Douala, Cameroon',
    period: '2025 — Present',
    summary:
      'Higher National Diploma in Software Engineering. The programme combines software development coursework with a compulsory industrial internship, which is how I ended up at NgahTech Group.',
    points: [
      'Coursework across software development, databases and computer networking.',
      'Industrial placement completed as part of the programme requirements.',
    ],
    todo:
      'Confirm your start date and expected graduation year — the period above is inferred from your coursework records and should be corrected if wrong.',
  },
];

/* -------------------------------------------------------------------------
   CV / résumé
   No CV file could be located in the sources available when this site was
   built. Drop a PDF at `public/cv/tebit-ramson-titih-cv.pdf` and flip
   `available` to true — the download buttons appear automatically.
   ------------------------------------------------------------------------- */
export const cv = {
  available: false,
  path: 'cv/tebit-ramson-titih-cv.pdf',
  filename: 'Tebit-Ramson-Titih-CV.pdf',
} as const;

/* -------------------------------------------------------------------------
   Profile photo
   No professional photograph could be found in the sources available when
   this site was built, so the hero renders a designed monogram instead.
   Drop a photo at `public/images/ramson.jpg` and flip `available` to true.
   ------------------------------------------------------------------------- */
export const portrait = {
  available: false,
  src: 'images/ramson.jpg',
  alt: 'Tebit Ramson Titih, software engineering student, photographed from the shoulders up.',
} as const;

/* -------------------------------------------------------------------------
   Navigation
   ------------------------------------------------------------------------- */
export const nav = [
  { label: 'Home', href: '/' },
  { label: 'About', href: '/about' },
  { label: 'Projects', href: '/projects' },
  { label: 'Résumé', href: '/resume' },
  { label: 'Contact', href: '/contact' },
] as const;
