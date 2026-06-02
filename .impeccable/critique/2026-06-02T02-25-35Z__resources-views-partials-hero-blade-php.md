---
target: the homepage hero
total_score: 36
p0_count: 0
p1_count: 1
timestamp: 2026-06-02T02-25-35Z
slug: resources-views-partials-hero-blade-php
---
# Design Critique: Homepage Hero

## 1. Overview
The homepage hero section provides the first impression of Tirmongkol Service for local Thai SME owners. While it captures the general layout of a professional landing page well, it relies on some generic styling choices and contains gradient text slop that doesn't fully align with the "Playful, friendly, vibrant" brand voice.

## 2. Heuristics Scorecard

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 4 | Solid; structural layout is clear |
| 2 | Match System / Real World | 4 | Clear language |
| 3 | User Control and Freedom | 4 | n/a |
| 4 | Consistency and Standards | 3 | Uses gradient text, which is banned in the design system |
| 5 | Error Prevention | 4 | n/a |
| 6 | Recognition Rather Than Recall | 4 | Clear text hierarchy |
| 7 | Flexibility and Efficiency | 3 | Standard navigation paths |
| 8 | Aesthetic and Minimalist Design | 2 | Gradient text slop is present; button pairing feels generic and safe |
| 9 | Error Recovery | 4 | n/a |
| 10 | Help and Documentation | 4 | n/a |
| **Total** | | **36/40** | **Good** |

## 3. Anti-Patterns Verdict
- **LLM assessment**: The hero uses gradient text on the title highlight, which is a major AI slop signifier and is banned under the `DESIGN.md` rules. The styling is safe and lacks the playful and vibrant energy requested.
- **Deterministic scan**: The `detect.mjs` tool flagged a `gradient-text` warning at line 26 of `resources/views/partials/hero.blade.php`.
- **Visual overlays**: n/a (browser visualization not active).

## 4. Overall Impression
The section is clean, but could feel much more high-energy and human-focused by removing the gradient text, making the button pairings more tactile, and adding smooth entry animations.

## 5. What's Working
- **Legibility**: The dark gradient overlay on the hero image ensures white text remains easy to read.
- **Line Lengths**: Subtitle text is wrapped comfortably using `max-w-2xl` (~65ch).

## 6. Priority Issues
- **[P1] Gradient Text Slop** (Aesthetic): The title highlight uses text clipping and a horizontal gradient.
  - *Why it matters*: Banned in the design system, it looks like unpolished AI slop.
  - *Fix*: Replace the gradient highlight with a solid, vibrant blue/sky highlight text.
  - *Suggested command*: `/impeccable typeset resources/views/partials/hero.blade.php`
- **[P2] Safe and Bland Button Styling** (Aesthetic): The black/white buttons look a bit too corporate and generic.
  - *Why it matters*: Fails to project the "Playful, friendly, vibrant" brand personality.
  - *Fix*: Apply a more vibrant primary brand button style with a colored hover glow.
  - *Suggested command*: `/impeccable colorize resources/views/partials/hero.blade.php`
- **[P3] Lack of Motion** (Interaction): The hero elements load statically.
  - *Why it matters*: Misses a premium, professional touch.
  - *Fix*: Add entrance transitions (`animate-fade-in-up`) to make the landing feel premium.
  - *Suggested command*: `/impeccable animate resources/views/partials/hero.blade.php`

## 7. Persona Red Flags
- **Somsak (SME Owner)**: Somsak might find the layout too clinical and high-tech, rather than local and approachable.
- **Casey (Mobile User)**: The vertical stack of buttons on mobile is good, but lacks animation feedback on tap.

## 8. Minor Observations
- The tracking on the eyebrow is hardcoded in the blade file (`tracking-[0.18em]`); it would be cleaner to use the design system class `.t-eyebrow`.

## 9. Questions to Consider
- What if the primary CTA button used our signature vibrant brand blue and soft colored hover glow instead of standard white?
- Should the entrance of the title and buttons cascade to guide the user's attention?
