---
name: tirmongkol-design
description: Use this skill to generate well-branded interfaces and assets for Tirmongkol Service, either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping.
user-invocable: true
---

Read the README.md file within this skill, and explore the other available files.
If creating visual artifacts (slides, mocks, throwaway prototypes, etc), copy assets out and create static HTML files for the user to view. If working on production code, you can copy assets and read the rules here to become an expert in designing with this brand.
If the user invokes this skill without any other guidance, ask them what they want to build or design, ask some questions, and act as an expert designer who outputs HTML artifacts _or_ production code, depending on the need.

## Quick map
- `README.md` — brand context, content fundamentals, visual foundations, iconography.
- `colors_and_type.css` — drop-in CSS variables (brand blue + slate, semantic roles) and a type scale.
- `fonts/` — Inter + Noto Sans Thai (loaded from Google Fonts; import snippet inside).
- `assets/logo-badge.svg` — the gradient "T" brand mark.
- `preview/` — small specimen cards (colors, type, spacing, components, brand).
- `ui_kits/website/` — interactive marketing-site recreation (hero, navbar, cards, footer, contact).
- `ui_kits/app/` — interactive Homework Scanner recreation (auth, dashboard, classroom, assignment, scan).

## Working notes
- One accent only: **brand blue `#3366ff`** (action `#1f47e6`) on a **slate** neutral canvas. Status =
  emerald (success) / amber (pending) / rose (destructive). No purple, no multi-hue gradients.
- Type: **Inter** (Latin) + **Noto Sans Thai** (Thai). Tight tracking on big headings; uppercase wide-tracked
  eyebrows.
- Shapes: `rounded-full` pill buttons; `rounded-2xl` cards with hairline `slate-200` rings + `shadow-sm`;
  `rounded-3xl` floating cards over photographic heroes.
- Icons: Feather/Heroicons line idiom (stroke 2). Reuse `ui_kits/*/icons.jsx`.
- Bilingual TH/EN product — keep copy plain, warm, benefit-led; sentence case in-app, Title Case on marketing.
- Both UI kits are React-via-Babel; lift the `.jsx` components and tokens directly when mocking.
