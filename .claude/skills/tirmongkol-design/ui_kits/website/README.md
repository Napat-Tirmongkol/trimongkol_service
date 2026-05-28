# Website UI Kit — Tirmongkol Service marketing site

A faithful, interactive recreation of the public marketing site (`trimongkol.com`): a single-page
React app that switches between **Home, Services, About, Contact** with the sticky scroll-aware navbar
and shared footer. Recreated from the Laravel/Blade source, not screenshots.

## Run
Open `index.html`. It loads React + Babel from CDN and the four `.jsx` files below. Click the nav to
move between pages; the "Try Free / Log In" buttons on Services link to the **app** kit
(`../app/index.html`).

## Files
| File | What's in it |
|------|--------------|
| `index.html` | App shell + page router (state-based) + scroll container |
| `icons.jsx` | `Icon` component — exact SVG paths lifted from the product (Feather/Heroicons idiom) |
| `site-chrome.jsx` | Design tokens (`window.T`), `Logo`, `Eyebrow`, `Button`, `Navbar`, `Hero`, `Footer` |
| `site-pages.jsx` | `HomePage`, `ServicesPage`, `AboutPage`, `ContactPage` + `Card`, `ServiceCard`, `StatStrip`, `CtaCard` |

## Components & patterns covered
- **Navbar** — fixed, transparent over the hero → `bg-white/90 backdrop-blur` on scroll; pill nav links
  with active state; EN/TH toggle pill; primary CTA button.
- **Hero** — full-bleed Unsplash photo + slate-900 gradient overlay, glass eyebrow pill, gradient
  highlight word, white/glass action buttons.
- **Floating cards** — stat strip and feature cards that overlap the hero with `-mt-24` + `shadow-2xl`.
- **Button** — `rounded-full`, five variants (`brand`, `dark`, `white`, `ghost`, `glass`), hover darken +
  arrow nudge.
- **Service cards** — numbered (01–06) gradient tiles; "coming soon" muted variant; hover lift.
- **CTA card** — `brand-600→700→900` gradient + `bg-grid` overlay.
- **Footer** — `slate-950`, logo lockup, menu + contact columns.
- **Contact form** — info panel (dark) + form panel with focus-ring inputs and a fake success toast.

## Copy & data
All strings are verbatim from `lang/en/site.php` (services list, stats, why-us, values, about story,
contact info). The Thai equivalents live in `lang/th/site.php` in the product.

## Known simplifications
- It's a cosmetic recreation: no real routing, form POST, or i18n switching (the EN/TH pill is inert).
- Hero photos load from Unsplash (the same URLs `config/site.php` ships as defaults).
- Icons use the kit's hand-matched SVG set; for pixel-exact glyphs copy paths from the Blade source.
