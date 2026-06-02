---
target: /accounting/onboarding
total_score: 30
p0_count: 0
p1_count: 2
timestamp: 2026-06-02T02-34-09Z
slug: resources-views-accounting-onboarding-blade-php
---
# Design Critique: Onboarding View

## 1. Overview
The onboarding screen serves as the entry point for the accounting package. The overall layout is clean, but it uses clinical, standard radio buttons and contains a major accessibility issue with the disabled submit button that directly violates the codebase rules. The typography also lacks hierarchy.

## 2. Heuristics Scorecard

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 3 | CTA button is disabled, but feedback contrast is poor |
| 2 | Match System / Real World | 4 | Clear labels and helper hints |
| 3 | User Control and Freedom | 3 | No escape or exit path from onboarding |
| 4 | Consistency and Standards | 2 | Disabled button violates CLAUDE.md standard rules |
| 5 | Error Prevention | 3 | Standard fields with required markers |
| 6 | Recognition Rather Than Recall | 4 | Field names are clear |
| 7 | Flexibility and Efficiency | 3 | Standard form elements |
| 8 | Aesthetic and Minimalist Design | 2 | Flat onboarding title, basic radio button styling, and hard divider |
| 9 | Error Recovery | 3 | Validation errors displayed at top of form |
| 10 | Help and Documentation | 3 | Uses browser-native tooltips for hints |
| **Total** | | **30/40** | **Good** |

## 3. Anti-Patterns Verdict
- **LLM assessment**: The button states violate contrast guidelines. The radio buttons are tiny, standard controls that make the app feel clinical and corporate rather than playful, friendly, and vibrant.
- **Deterministic scan**: The `detect.mjs` tool flagged a `flat-type-hierarchy` warning at line 21. It also flagged `em-dash-overuse` (7 em-dashes), which is a **false positive** caused by Blade comment delimiters (`{{-- ... --}}`).
- **Visual overlays**: n/a (browser visualization not active).

## 4. Overall Impression
The onboarding form is functional, but lacks visual polish. Applying the project's standard disabled opacity pattern to the button, refactoring the business type radios into modern Radio Cards, and tightening the input borders and focus rings will make this form feel highly premium and accessible.

## 5. What's Working
- **Validation feedback**: Conditional Laravel validation errors are displayed clearly in a rose-50 alert container.
- **Form Bindings**: Clean AlpineJS data bindings (`x-model`, `ready` getter) enable a smooth dynamic form state.

## 6. Priority Issues
- **[P1] Disabled Button Contrast Violation** (Consistency & Standards)
  - **Why it matters**: Violates `CLAUDE.md` guideline ("ปุ่ม disabled ต้องอ่านได้เสมอ"). Contrast ratio is ~2.2:1 (below WCAG 4.5:1), rendering the button text unreadable when disabled.
  - **Fix**: Remove custom disabled backgrounds/text, and use the project standard `disabled:opacity-60`.
  - **Suggested command**: `/impeccable polish resources/views/accounting/onboarding.blade.php`
- **[P1] Small Radio Targets** (Aesthetic & Minimalist Design)
  - **Why it matters**: Standard radios look clinical and are difficult for mobile users to tap quickly.
  - **Fix**: Convert the radio options into modern, rounded Radio Cards using Tailwind `has-[:checked]` utilities.
  - **Suggested command**: `/impeccable layout resources/views/accounting/onboarding.blade.php`
- **[P2] Flat Title Hierarchy** (Typography)
  - **Why it matters**: The onboarding heading size (`text-lg`) is too close to label size, creating a flat typographic scale.
  - **Fix**: Upgrade the heading to `text-2xl font-bold` or `text-xl font-bold`.
  - **Suggested command**: `/impeccable typeset resources/views/accounting/onboarding.blade.php`

## 7. Persona Red Flags
- **Jordan (First-Timer)**: Jordan might get confused by the disabled submit button, as its low contrast makes the text unreadable, hiding the instruction of what to do next.
- **Casey (Mobile User)**: The standard radio buttons are tiny tap targets, making them hard to touch one-handed on mobile.

## 8. Minor Observations
- The `<hr class="border-slate-200">` line is very stark; replacing it with a subtle container padding or borderless separation could soften the design.

## 9. Questions to Consider
- Should we display helper hints inline under fields instead of relying on native browser hover tooltips?
- What if the Radio Cards contained small descriptive icons for "Company" and "Individual" to improve clarity?
