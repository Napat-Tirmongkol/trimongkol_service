# Tirmongkol Service — Design System

A design system reverse-engineered from the **Tirmongkol Service** product codebase, for generating
on-brand interfaces, mocks, and assets.

---

## What is Tirmongkol Service?

Tirmongkol Service is a **software studio for Thai SMEs** (small & medium businesses). The brand has two
clearly separated surfaces:

1. **The marketing website** (`trimongkol.com`) — a public, bilingual (Thai / English) site that sells
   custom business software: queue booking, product/inventory management, POS, membership/CRM, business
   websites, and HR systems. It is the storefront for a consulting-style "we build it for you" offering.

2. **The product app — "Homework Scanner" (ระบบเช็คงานนักเรียน)** — the first live, self-serve SaaS the
   studio ships. Teachers upload a class roster, the system generates a barcode/QR per student, the teacher
   prints labels and sticks them on notebooks, then **scans with a phone camera** to mark homework as
   submitted (and optionally score it) — "10× faster than ticking off names by hand." It includes
   classrooms, students, assignments, a gradebook, workspaces/teams, subscription plans, and an admin
   portal (users, leads, audit log, security, system tools).

The two surfaces share one visual language (blue brand + slate neutrals, Inter type, rounded cards) but
differ in tone: the website is **photographic and confident**; the app is **clean, dense, and utilitarian**.

### Positioning & audience
- **Website audience:** Thai SME owners (restaurants, clinics, salons, shops) evaluating a software partner.
- **App audience:** Thai school teachers checking homework — non-technical, often on a phone.
- Everything is **bilingual TH / EN**, toggled by a session cookie; Thai is the primary market.

---

## Sources used to build this system

Everything here was lifted directly from the product source code (not screenshots):

- **GitHub repository:** `Napat-Tirmongkol/trimongkol_service`
  → https://github.com/Napat-Tirmongkol/trimongkol_service
  A **Laravel 11 + Blade + Tailwind CSS v4 + Livewire 3 + Alpine.js** application.

Key files referenced:
- `resources/css/app.css` — the `@theme` block: brand color scale, font stack, animations, `bg-grid` &
  `text-gradient` utilities. **The authoritative token source.**
- `resources/views/layouts/marketing.blade.php`, `guest.blade.php`, `navigation.blade.php` — page shells.
- `resources/views/partials/{hero,marketing-navbar,marketing-footer}.blade.php` — marketing chrome.
- `resources/views/pages/{home,services,about,contact}.blade.php` — marketing pages.
- `resources/views/{classrooms,assignments,auth}/*.blade.php` — the Homework Scanner app.
- `lang/en/site.php`, `lang/en/app.php` — all product copy (mirrored in `lang/th/*`).
- `config/site.php` — contact info + hero image URLs.

> **Reader tip:** if you have access, browse the repo above to go deeper — the Blade templates are the
> ground truth for spacing, states, and component structure that a static design system can only summarize.

> **Note on the name:** the brand string is **"Tirmongkol Service"** (used in `config/site.php` and all
> copy), while the domain and repo are spelled **trimongkol**. We use *Tirmongkol Service* as the display
> name everywhere, matching the product.

---

## CONTENT FUNDAMENTALS

How the brand writes.

**Voice:** plain, warm, and reassuring — a competent partner, not a hype machine. The website explicitly
positions against jargon: *"We don't just write code — we understand business."*

**Person & address:**
- Marketing speaks as **"we"** (the studio) to **"you/your business"** — e.g. *"We build end-to-end
  business management systems… tailored to your business."*
- The app speaks **to the teacher directly** and casually — *"Hi, :name 👋"*, *"Manage your classrooms…"*

**Casing:** Marketing headings are **Title Case** (*"Why Choose Us", "Build Software That Powers Your
Business"*). App UI labels are **Sentence case** (*"Add assignment", "Mark submitted", "Bulk import"*).
Eyebrows/kickers are **UPPERCASE with wide tracking** (*"OUR SERVICES", "SOFTWARE SOLUTIONS"*).

**Sentence length:** short and benefit-led. Headlines are punchy; supporting lines are one sentence.
Feature bullets are fragments, not sentences (*"Mobile/web booking", "Low-stock alerts"*).

**Numbers & proof:** confident round stats — *50+ Happy Clients, 120+ Projects Delivered, 8+ Years,
24/7 Support*. Used sparingly, in a floating stat strip.

**Thai-first localization rules (from the repo's CLAUDE.md):**
- Every English key has a Thai twin; the two stay in sync key-for-key.
- Prefer everyday Thai over transliterated IT/marketing English in Thai UI (e.g. "lead" → "ข้อความติดต่อ").
- Thai uses **Noto Sans Thai / Sarabun**; never mix in stray English jargon when a clear Thai word exists.

**Emoji:** used **sparingly and only for warmth**, never as iconography. Confirmed spots: `👋` in the app
greeting, `🎉` on "Everyone has submitted!", `💸` on the "Free forever" pill, `⚠️` on the admin
impersonation banner. Core navigation, buttons, and labels use **no emoji**.

**Microcopy examples:**
- CTA: *"Start a Free Consultation"*, *"Get a Quote"*, *"Try Free"*, *"Open app"*
- Empty state: *"No classrooms yet — Start by creating your first classroom…"*
- Success toast: *"Message sent — we'll get back to you as soon as possible."*
- App hint: *"Upload Excel roster → auto barcodes"* (arrow-driven "do X → get Y" phrasing is common).

---

## VISUAL FOUNDATIONS

**Color.** One accent — **brand blue** (`#3366ff`, action `#1f47e6`/`#1936b8`) — on a **slate** neutral
canvas. White and `slate-50` page backgrounds; `slate-100` for alternating section bands; `slate-900`
for dark feature cards and `slate-950` for the footer. Status colors are conventional: **emerald** =
success/submitted, **amber** = coming-soon/pending/assignments, **rose** = destructive/errors/logout.
No secondary brand hue, no purple, no multi-color gradients beyond the blue ramp.

**Type.** **Inter** for everything Latin (weights 400–900; hero uses 800/extrabold), **Noto Sans Thai**
for Thai. Tight tracking on big headings (`-0.02em`), wide tracking on uppercase eyebrows (`0.18em`).
Numbers use `tabular-nums`. Monospace only for student barcode codes. See `colors_and_type.css`.

**Backgrounds & imagery.**
- **Marketing heroes are full-bleed photographs** (warm, professional, real workplaces — sourced from
  Unsplash; office/handshake/laptop scenes) covered by a **slate-900 top-to-bottom gradient overlay**
  (`/70 → /40 → /70`) so white text stays legible. Imagery vibe: **warm, natural, human, slightly
  desaturated** — not cold stock-tech.
- A signature **`bg-grid`** texture (faint 32px slate grid lines at ~5–10% opacity) sits behind dark
  brand/CTA cards.
- The app uses **flat white/slate surfaces**, no photography.

**Gradients.** Restrained and always within the blue ramp:
- The **"T" logo badge**: `135deg, brand-500 → brand-700`.
- **CTA / feature cards**: `brand-600 → brand-700 → brand-900` with a faint grid overlay.
- **`text-gradient`** on accent words (hero highlight uses `brand-300 → brand-200 → white` on photos).

**Corner radii.** Soft and generous. `rounded-md` (6px) for inputs and in-app buttons; `rounded-xl`
(12px) for icon tiles & menus; `rounded-2xl` (16px) for standard cards; `rounded-3xl` (24px) for hero
floating cards & big feature panels; `rounded-full` for nav links, pills, CTA buttons, and avatars.

**Cards.** White fill, hairline border via **`ring-1 ring-slate-200`** (or `border border-slate-200`),
`rounded-2xl`, `shadow-sm`. **Floating cards** that overlap the hero use `rounded-3xl` + `shadow-2xl`
+ a negative top margin (`-mt-24`) and `ring-1 ring-slate-200/50`. Dark cards are `slate-900` with
white text and `bg-white/5 ring-white/10` insets.

**Shadows / elevation.** Subtle and layered: `shadow-sm` resting → `shadow-md`/`shadow-lg` raised →
`shadow-xl`/`shadow-2xl` for floating/CTA. Brand buttons and the logo carry a **colored glow**
(`shadow-brand-500/20`–`/30`). Borders do the lifting at rest; shadow grows on hover.

**Hover states.** Cards: **lift** (`-translate-y-0.5`) + shadow deepens + border tints to
`ring-brand-300`. Solid buttons: **darken one step** (`brand-600 → brand-700`, `slate-900 → slate-800`).
Ghost/outline buttons: fill with a faint tint (`hover:bg-slate-100`). Links: brand-700 → brand-900.
Arrow icons nudge right (`translate-x-0.5`) on hover.

**Press / focus.** Inputs focus to **brand border + a soft brand ring** (`ring-2 ring-brand-500/20`).
Radio "cards" check-state: `border-brand-500 bg-brand-50` (via `has-[:checked]`). No heavy scale-on-press.

**Borders & dividers.** Hairline `slate-200`; list rows divided by `divide-slate-100`. Pills and badges
often carry a `ring-1` in a tinted shade (e.g. `bg-emerald-50 text-emerald-700 ring-emerald-200`).

**Transparency & blur.** Used for the **sticky navbar** (`bg-white/90 backdrop-blur` after scroll;
transparent over the hero) and for **glass insets on dark/photo surfaces** (`bg-white/10 backdrop-blur`,
e.g. the hero eyebrow pill and contact-card icon tiles).

**Buttons (shape language).** Almost all are **`rounded-full`**. Primary on the marketing site is either
white-on-photo (`bg-white text-slate-900`) or `bg-slate-900 text-white`; the brand-blue button
(`bg-brand-600 text-white`) is the in-product primary. A right-arrow SVG frequently trails the label.
Disabled state uses **`disabled:opacity-60`** (never a washed-out background that kills contrast — a
hard rule from the repo's CLAUDE.md).

**Layout.** Centered `max-w-7xl` (marketing) / `max-w-3xl–7xl` (app) containers with `px-4 sm:px-6`
gutters. Generous vertical rhythm (`mt-24`/`py-20`+ between marketing sections). Heavy use of CSS grid
(`sm:grid-cols-2 lg:grid-cols-3/4`) with `gap`. The fixed navbar overlays the hero; floating cards
straddle section boundaries with negative margins.

**Animation.** Gentle and quick. Two keyframes only: **`fade-in-up`** (0.6s ease-out, 20px rise) and
**`fade-in`** (0.8s ease-out). `scroll-behavior: smooth`. Hover transitions ~150–300ms. No bounces,
no springy/elastic motion, no parallax.

---

## ICONOGRAPHY

**System:** the product uses **inline SVG line icons** in a **Heroicons / Feather** idiom —
`viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`, `stroke-width` **2** (or **2.5/3** for
small or emphasis marks), round line caps/joins. Icons inherit text color via `currentColor` and are
sized in px (14–24). There is **no icon font** and **no icon sprite** — each icon is hand-written into
the Blade templates.

Common icons in use: right-arrow (`line` + `polyline`) trailing CTAs, checkmark (`polyline 20 6 9 17
4 12`) for features/success, users/people, clipboard-check (assignments), bar-chart (gradebook),
QR/scan frame, hamburger & close, chevrons, mail/phone/clock/chat (contact), eye (vision), play-in-circle
(mission).

**This kit's approach:** because the icons are hand-rolled (not a packaged set), the UI kits here load
**[Lucide](https://lucide.dev)** from CDN — the maintained successor to Feather, which matches the
stroke-2 outline style almost exactly. **⚠️ Substitution flag:** Lucide is a close stand-in, not the
literal SVGs from the repo; a few product icons are Heroicons-outline rather than Feather, so individual
glyphs may differ slightly. If pixel-exact icons matter, copy the specific `<svg>` paths out of the Blade
templates instead.

**Logo / brand mark:** there is **no separate logo image** in the product. The mark is a **typographic
"T" badge** — a `rounded-xl` square with the `brand-500 → brand-700` gradient, a white bold "T", and a
soft brand glow — set beside the wordmark "Tirmongkol Service." It is recreated here in
`assets/logo-badge.svg` (and trivially as HTML/CSS). *(The repo's `application-logo.blade.php` is just
the default Laravel logo and is **not** the brand mark — ignore it.)*

**Emoji as icons:** never in core UI; only the few warmth accents listed under Content Fundamentals.

---

## Index / manifest

Root files:
- **`README.md`** — this file.
- **`colors_and_type.css`** — color + type tokens (CSS custom properties) and a semantic type scale.
- **`SKILL.md`** — Agent-Skill front-matter so this folder can be used directly in Claude Code.

Folders:
- **`assets/`** — brand mark (`logo-badge.svg`) and any copied visual assets.
- **`fonts/`** — note on web fonts (Inter + Noto Sans Thai are loaded from Google Fonts).
- **`preview/`** — small HTML cards that populate the Design System tab (colors, type, spacing,
  components, brand).
- **`ui_kits/website/`** — hi-fi recreation of the marketing site (hero, navbar, footer, service cards,
  stat strip, CTA, contact form). See its `README.md`.
- **`ui_kits/app/`** — hi-fi recreation of the Homework Scanner app (auth, dashboard, classroom,
  assignment + submission tracking, scan screen). See its `README.md`.

*(No slide template was provided in the source, so `slides/` is intentionally omitted.)*
