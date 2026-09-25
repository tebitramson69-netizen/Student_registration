# Business Plan — From `Student_registration` to a Revenue-Generating Product

**Owner:** Ramson Titih (Cameroon)
**Constraint:** near-zero capital, solo founder, needs income soon
**Stack in hand:** HTML/CSS/JS, PHP, MySQL, Git, XAMPP/Apache
**Date:** 2026-09-25

---

## 1. Market Research — what is actually already taken

Before choosing anything, I checked whether the obvious ideas are occupied.
They are. This section exists so we do not build into a wall.

### 1.1 School fee collection / school management — SATURATED
- **SkulPay** (`skulpay.cm`) positions itself as the *national* platform for
  online tuition collection in private primary and secondary schools via MTN MoMo.
- **MinesecPay** (`minesecpay.cm`) is the equivalent for public secondary schools.
- **MTN Cameroon signed an MoU with MINESEC** for mobile-money school fee payment.
- **Go4School**, **EduManager Pro**, **EduCam Pro**, **ProsoftAfrica** all sell
  full school management (enrolment, grades, MoMo finance, SMS) in Cameroon.

**Verdict:** a solo founder cannot beat incumbents who hold a ministry
relationship and an MNO partnership. **Do not build a school ERP.**

### 1.2 Njangi / tontine apps — SATURATED
- **NjangiPulse** (Douala, MoMo via NotchPay), **Tontiin**, **Njangi Digital**,
  **Djangui**, **EAA / entraidaa.app** are all live and competing.

**Verdict:** crowded, and it is a *money-custody* business, which drags in
BEAC/COBAC regulatory exposure. **Do not build a tontine app.**

### 1.3 Event ticketing — SATURATED
- **Grena Tickets**, **showwupp**, **Tikiti.Africa**, **Ayatickets** all offer
  MoMo/Orange ticket sales with QR check-in for Cameroon.

**Verdict:** **Do not build a ticketing platform.**

### 1.4 Payments themselves — SATURATED, but useful as infrastructure
- **CamPay**, **Fapshi**, **MeSomb**, **Notch Pay**, **Monetbil** all expose
  REST APIs for MTN MoMo + Orange Money collection.

**Verdict:** do not compete with them — **consume** them. They settle to the
merchant's own account, which means we can accept payments *without ever
holding client funds*, and therefore without needing a payment licence.

### 1.5 The pattern
Every saturated space above is **consumer-facing, visible and VC-attractive**.
What stays open in this market is the opposite: **boring back-office work for
small businesses, sold face to face, that nobody wants to support.**
That is exactly where a competent solo engineer wins.

### 1.6 Demand-side realities (documented, not assumed)
- ~88% of connected Cameroonian consumers already buy through WhatsApp — the
  channel is WhatsApp, not an app store.
- Real adoption barriers: data cost, unreliable power/internet, low digital
  literacy, distrust, and preference for cash.
- Cameroon's Finance Law schedules **electronic invoicing from 2026**, with
  certified solutions and approved providers — a future forcing function for
  registered SMEs (see §7, Year 2).

---

## 2. Devil's Advocate — attacking every plan, including my own

### Attack 1: "Just build a startup."
**Rebuttal:** You are broke. A startup is a machine that consumes money before
it produces any. With zero runway, a pure product play fails not because the
idea is bad but because you run out of food before month 6.
**Consequence:** the plan must produce cash in **weeks**, not quarters.

### Attack 2: "Sell SaaS subscriptions to Cameroonian SMEs."
**Rebuttal:** willingness to pay for pure software is low, support cost is high,
digital literacy is low, and churn after the first month is brutal. ARPU of
5,000 FCFA against a customer who phones you four times a week is a loss.
**Consequence:** price must include a **setup fee paid up front**, and the
product must be simple enough to need near-zero support.

### Attack 3: "Do freelancing on Upwork/Fiverr instead."
**Rebuttal:** it is the fastest USD, and it is genuinely worth running — but it
is a job, not an asset. Income stops the day you stop typing, and it builds
nothing you own. Also realistically 4–8 weeks to a first contract without an
established profile.
**Consequence:** keep it as a **parallel cash line**, never as the strategy.

### Attack 4 (against my own recommendation): "The payment aggregators already
do this — CamPay gives a payment link, why do you exist?"
**Rebuttal:** a payment link gives you a *transaction*. It does not give you a
**roster**: who is enrolled, who has paid which instalment, who still owes,
what the verifiable receipt number is, and a reconcilable end-of-month
statement. That link between *person* and *payment* is the product.
**Consequence:** we must be a **records product**, not a payments product.

### Attack 5 (the strongest one): "Most of this money is cash, and it will
stay cash."
**Rebuttal:** correct, and this is the insight that decides the design.
**Do not bet on digitising the payment. Bet on digitising the receipt.**
The software must record a cash payment just as happily as a MoMo payment.
MoMo is an option, not a requirement. This also removes us from direct
competition with every platform in §1.

### Attack 6: "You will build a perfect app and sell zero copies."
**Rebuttal:** this is the single likeliest failure mode for an engineer.
**Consequence:** the plan forces **selling before building**. No multi-tenant
version is written until one real customer has paid a deposit.

### Attack 7: "Support and travel will eat you alive."
**Consequence:** one codebase, one hosted instance, **no custom features for
the first 6 months**, WhatsApp support with published hours, onboarding done
in a single visit with a printed one-page guide.

### Attack 8: "No electricity, no internet at the customer's premises."
**Consequence:** server-rendered PHP, no SPA, tiny pages, works on a 30-dollar
Android over 3G. Receipts printable on paper. No dependency on the centre
having a computer at all.

---

## 3. The Decision

> **Build a fee-collection and student-records back-office for the small
> private training institutions that the big platforms ignore — and sell it
> first as a done-for-you service, not as software.**

**Beachhead customers:** private vocational and computer training centres,
professional exam-prep centres, driving schools, language schools, tailoring
and trade academies.

**Why this segment survives the devil's advocate:**
- They collect tuition **in instalments, in cash, recorded in exercise books.**
  That is a live money leak and a live dispute source — real pain, not convenience.
- They are too small and too informal for Go4School/SkulPay, which chase formal
  MINESEC-registered schools and hold ministry deals. **No head-on collision.**
- The **owner is the decision-maker**. One conversation closes the sale — no
  procurement, no committee, no ministry.
- They already spend money on signage, flyers, rent, sometimes a website — so
  the "will they pay for anything at all" question is already answered yes.
- Programme fees are meaningful (roughly 150,000–400,000 FCFA per programme),
  so a 1% fee or a flat monthly price is easy to justify.
- **Your existing code is already ~40% of it**: the registration form, the
  searchable/sortable table, the pagination and the CSV export in this repo are
  literally the student registry.

**What the product does (v1, deliberately small):**
1. Register a student into a programme/intake.
2. Record every payment — **cash, MoMo or bank** — against that student.
3. Issue a **numbered, verifiable receipt** (printable + SMS/WhatsApp text).
4. Show, at any moment, **who owes what** (the balance/arrears list).
5. Export the month's collections for the owner's own records.
6. Optional MoMo collection via CamPay/Fapshi, **settled directly to the
   centre's own account — we never hold client money.**

**What v1 explicitly does NOT do:** grades, timetables, report cards, payroll,
attendance, a mobile app, a parent portal. Every one of those is how this dies.

---

## 4. Business Model

| Line | Price (FCFA) | Timing | Why it works |
|---|---|---|---|
| Setup + onboarding + data entry | 25,000 – 75,000 one-off | **Paid up front** | Cash in week 1–2; filters out non-serious buyers |
| Subscription | 5,000 – 15,000 / month | Monthly or annual | Recurring base |
| Transaction fee (optional) | ~1% of collected, or 100/receipt | Per payment | Aligned: they pay when they get paid |
| SMS credits | pass-through + margin | Per use | Small, real margin |

**Honest revenue math — no fantasy:**
- 3 pilot customers × 50,000 setup = **150,000 FCFA** in month 1–2.
- 20 centres × 10,000/month = **200,000 FCFA/month** (~€305).
- 60 centres × 10,000/month = **600,000 FCFA/month** (~€915).

60 paying centres is a realistic **ceiling for a solo founder in year one**,
and it is a real income in Cameroon. It is not a unicorn. State this plainly to
yourself now so you do not quit in month 4 when it is not a unicorn.

---

## 5. Execution Plan

### Phase 0 — Validate before building (Week 1) — **NO CODE**
1. List **20 training centres / driving schools** you can physically reach.
2. Visit or call **10**. Ask only, never pitch:
   - How do you record who has paid tuition today?
   - How do you know at month-end who still owes?
   - Has a payment ever gone missing or been disputed?
   - What do you do when a student says "I already paid"?
3. **Gate:** if fewer than 5 of 10 describe an exercise book plus a real
   arrears/dispute problem, **this plan is wrong — stop and re-pick the
   segment.** Write down the actual answers, not your hopes.

### Phase 1 — Demo + first paying pilot (Weeks 2–4)
4. Build the **single-tenant demo** on top of this repo (see §6).
5. Show it on a phone to the 5 warmest centres.
6. Close **1–3 pilots at a discounted setup fee, collected before delivery.**
7. Onboard in person: enter their real current students with them.
   **Gate:** money in hand before any multi-tenant work begins.

### Phase 2 — Harden and multi-tenant (Weeks 5–10)
8. Add accounts/roles, per-centre data isolation, audit trail.
9. Deploy to a cheap VPS or shared host with a `.cm` or `.com` domain.
10. Add CamPay/Fapshi collection, settled to the centre's own account.
11. Move the pilots onto the hosted version.

### Phase 3 — Repeatable sales (Months 3–6)
12. One-page printed flyer + a 90-second phone demo video in English and French.
13. Target **2 new centres per week** by walking the commercial streets.
14. Publish a public "verify your receipt" page — every student who checks a
    receipt sees your brand. Free distribution.
15. **Gate at month 6:** if you are not at 15 paying centres, the problem is
    distribution, not features. Fix selling, do not add features.

### Parallel cash line (runs throughout, so you eat)
- Local paid gigs: business websites with WhatsApp ordering + MoMo payment
  links, fixed price 75,000–200,000 FCFA. Same PHP skills, immediate cash.
- Keep this running until the subscription base covers your monthly costs.

---

## 6. Technical Plan (v1)

**Reuse from this repo:** the registration form, the searchable + sortable +
paginated table, and the CSV export are the foundation of the student registry.

**Rebuild properly before it touches money — the current code is a demo, not a
product.** Existing issues that must be fixed:
- `connection.php`: DB credentials hard-coded, `root` with an empty password.
- Queries use `mysqli_real_escape_string` and string interpolation throughout
  (`form.php`, `table.php`, `update.php`) instead of **prepared statements**.
- `table.php`: `$sort`/`$order` are interpolated straight into the `ORDER BY`.
  They are whitelisted today, which holds — but it is a fragile pattern to keep.
- **No authentication, no authorization, no sessions, no CSRF protection** —
  anyone who reaches `delete.php?id=5` deletes a record.
- `update.php` renders `$student` without checking the record was found.
- The pagination count ignores the active search filter, so page counts are
  wrong while searching.
- No input validation beyond HTML `required`.

**v1 architecture (same stack, done properly):**
- PHP 8 + MySQL, **PDO with prepared statements everywhere**.
- Config via a `.env`-style file kept out of git; `connection.php` reads it.
- Session-based auth, password hashing with `password_hash()`, roles
  (`owner`, `staff`), CSRF tokens on every state-changing form.
- Schema: `institutions`, `users`, `programmes`, `students`, `enrolments`,
  `payments`, `receipts` — with foreign keys, `NOT NULL` constraints, indexes
  on the lookup columns, and money stored as integer minor units (never float).
- Every payment row is **append-only**; corrections are reversal entries, not
  edits. This is what makes the record trustworthy in a dispute.
- Server-rendered pages, minimal CSS, no JS framework — fast on 3G.
- Receipts: sequential per-institution number + short verification code.
- MoMo via CamPay or Fapshi REST API, added only in Phase 2.

---

## 7. Where this goes in Year 2

Once you hold the payment and enrolment records of a few dozen institutions:
- **Compliance layer:** Cameroon's 2026 electronic-invoicing obligation forces
  registered businesses onto certified invoicing. Being the system that already
  holds their sales records is the right position from which to sell that.
- **Adjacent verticals:** the same "roster + instalment payments + verifiable
  receipt" engine fits clinics, gyms, cooperatives, landlords, and associations.
- Only then is there a real startup. Year 1 is a services-funded product build.

---

## 8. The honest summary

- The obvious ideas are already taken by better-resourced competitors.
- The opening is boring back-office software sold face to face.
- Being broke means **services first, product second** — cash engine funds asset.
- Your biggest risk is not competition or technology. It is **building without
  selling**. Phase 0 has no code in it for that exact reason.
