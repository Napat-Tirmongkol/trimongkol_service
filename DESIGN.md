---
name: LangBaan Service
description: Playful, friendly, and vibrant software studio for Thai SMEs.
colors:
  primary: "#3366ff"
  primary-action: "#1f47e6"
  primary-hover: "#1936b8"
  neutral-bg: "#ffffff"
  neutral-bg-muted: "#f8fafc"
  neutral-bg-section: "#f1f5f9"
  neutral-bg-inverse: "#0f172a"
  neutral-bg-inverse-2: "#020617"
  neutral-fg: "#0f172a"
  neutral-fg-muted: "#475569"
  neutral-fg-secondary: "#64748b"
  neutral-fg-light: "#94a3b8"
  border: "#e2e8f0"
  border-strong: "#cbd5e1"
typography:
  display:
    fontFamily: "Inter, Noto Sans Thai, sans-serif"
    fontSize: "clamp(2.25rem, 5vw, 4.5rem)"
    fontWeight: 800
    lineHeight: 1.05
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Inter, Noto Sans Thai, sans-serif"
    fontSize: "clamp(1.875rem, 3vw, 3rem)"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Inter, Noto Sans Thai, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.01em"
  body:
    fontFamily: "Inter, Noto Sans Thai, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "Inter, Noto Sans Thai, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    letterSpacing: "0.18em"
rounded:
  sm: "0.375rem"
  md: "0.75rem"
  lg: "1rem"
  xl: "1.5rem"
  full: "9999px"
spacing:
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary-action}"
    textColor: "#ffffff"
    rounded: "{rounded.full}"
    padding: "14px 28px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  card:
    backgroundColor: "{colors.neutral-bg}"
    rounded: "{rounded.lg}"
    padding: "24px"
---

# Design System: LangBaan Service

## 1. Overview

**Creative North Star: "The SME Playground"**

The SME Playground aesthetic brings a playful, friendly, and highly vibrant experience to Thai SME owners evaluating custom software solutions. Rather than the cold, mechanical, or overly serious look typical of corporate SaaS platforms, the interface balances professionalism with high-energy visuals, approachable typography, and friendly, tactile component interactions.

This design system explicitly rejects boring SaaS-cream/sand palettes, uninspired card grids, and dry, corporate jargon. It embraces bold brand blue accents, deep slate backdrops with signature faint grid overlays, and warm, photographic representations of real Thai businesses.

**Key Characteristics:**
- Friendly, rounded geometries (`rounded-full` pills and `rounded-2xl` cards).
- Vibrant, focused accentuation (brand blue `#3366ff` action elements).
- Intuitive and responsive micro-animations that make actions feel satisfying.
- High-contrast readability with robust typography scaling.

## 2. Colors

The color palette is built around a single, vibrant brand blue accent resting on clean slate neutrals, creating a high-contrast and high-energy feel.

### Primary
- **Vibrant Brand Blue** (#3366ff): The primary brand identifier. Used selectively to draw focus to core interactive actions, badges, and key accent paths.
- **Action Blue** (#1f47e6): The primary background color for interactive elements (such as buttons).
- **Hover Blue** (#1936b8): Used for hover states on primary brand elements.

### Neutral
- **Ink / Slate 900** (#0f172a): The primary color for headings, text, and dark brand panels.
- **Slate 600** (#475569): The workhorse body copy color, ensuring high legibility.
- **Slate 200** (#e2e8f0): Used for borders, card dividers, and hairline containers.
- **White** (#ffffff): Used for clean, flat page backgrounds.
- **Slate 50** (#f8fafc): Muted background color for sections and container surfaces.

### Named Rules
**The Focused Accent Rule.** The primary brand blue accent is reserved for interactive actions and selective focus highlights. It should cover no more than 10% of any screen surface to maintain its impact.

## 3. Typography

**Display Font:** Inter (for Latin headings), Noto Sans Thai (for Thai headings)
**Body Font:** Inter (for Latin body), Noto Sans Thai / Sarabun (for Thai body)

### Hierarchy
- **Display** (ExtraBold (800), `clamp(2.25rem, 5vw, 4.5rem)`, 1.05 line-height): Large hero headings. Characterized by tight letter-spacing (`-0.02em`) to look compact and designed.
- **Headline** (Bold (700), `clamp(1.875rem, 3vw, 3rem)`, 1.15 line-height): Main section titles. Uses `text-wrap: balance` to prevent orphans.
- **Title** (Bold (700), `1.5rem` (24px), 1.2 line-height): Card titles and sub-section headers.
- **Body** (Regular (400), `0.875rem` (14px), 1.6 line-height): Standard copy. Capped at 65–75ch for readable line lengths.
- **Label** (SemiBold (600), `0.75rem` (12px), `0.18em` tracking): Section eyebrows and uppercase badges.

### Named Rules
**The No-Jargon Casing Rule.** Heading titles use Title Case in English and natural, jargon-free spacing in Thai. Eyebrows must always be UPPERCASE with `tracking-[0.18em]` (wide tracking) to act as clear kickers.

## 4. Elevation

The system is flat at rest but uses subtle, layered shadows to respond to user interactions, giving a tactile, physical quality to active surfaces.

### Shadow Vocabulary
- **Tactile Hover Glow** (`0 10px 15px -3px rgba(51, 102, 255, 0.30)`): Used under primary brand buttons and the logo on hover.
- **Standard Card Elevation** (`0 1px 2px 0 rgba(0, 0, 0, 0.05)`): Subtle border-based elevation for white cards at rest.
- **Hero Floating Elevation** (`0 25px 50px -12px rgba(15, 23, 42, 0.10)`): Strong shadow used for elements overlapping hero sections.

### Named Rules
**The Interaction Lift Rule.** Elements that can be clicked (like cards) must lift slightly (`-translate-y-0.5`) and experience a shadow deepening on hover, returning to flat at rest.

## 5. Components

### Buttons
- **Shape:** Rounded-full (pill-shaped, `9999px`) for a friendly, approachable feel.
- **Primary:** Action Blue (`#1f47e6`) background with White (`#ffffff`) text. Padding: `14px 28px`.
- **Hover / Focus:** Transitions to Hover Blue (`#1936b8`) on hover. Arrow SVGs within buttons translate right by `0.5` units on hover.
- **Disabled State:** Uses `disabled:opacity-60` to dim the button while preserving text legibility, strictly keeping the original background color to maintain contrast.

### Cards / Containers
- **Corner Style:** Rounded-2xl (`1.5rem` / 24px) for prominent hero panels, and Rounded-xl (`1.0rem` / 16px) for standard grids.
- **Background:** White (`#ffffff`) at rest, or Slate 900 (`#0f172a`) with a `bg-white/5` overlay for dark brand sections.
- **Border:** Hairline Slate 200 (`#e2e8f0`) border (or `ring-1 ring-slate-200`) to define bounds.

### Inputs / Fields
- **Style:** Stroke border (`#cbd5e1`), white background, rounded-md (`0.375rem` / 6px).
- **Focus:** Transition border to Action Blue (`#1f47e6`) with a soft brand focus ring (`ring-2 ring-brand-500/20`).

### Navigation
- **Style:** Clean, floating layout. Transparent backdrop when resting over hero images, transitioning to a sticky, blurred background (`bg-white/90 backdrop-blur`) upon scroll. Links transition to brand-700 on hover.

## 6. Do's and Don'ts

### Do:
- **Do** use `rounded-full` (pill shapes) for primary action buttons to reinforce the playful register.
- **Do** pair Noto Sans Thai with Inter to maintain typographic consistency across translations.
- **Do** verify text contrast against its background, ensuring ≥4.5:1 ratio (especially on disabled states).
- **Do** use desaturated, human-focused background photos in hero sections with dark overlays.

### Don't:
- **Don't** use SaaS-cream, paper, or sand backgrounds (such as `#faf7f2` or `--paper` tokens).
- **Don't** use gradient text or text-clipping tricks for headings.
- **Don't** use glassmorphic blur effects on regular cards or default UI screens.
- **Don't** add side-stripe borders (borders > 1px) to alert or feature boxes.
- **Don't** place tiny wide-tracked uppercase eyebrows on every single section.
