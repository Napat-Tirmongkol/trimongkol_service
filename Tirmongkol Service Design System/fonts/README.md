# Fonts

Tirmongkol Service uses **two web fonts**, both served from **Google Fonts** (no self-hosted files in
the product):

| Role | Family | Weights used | Source |
|------|--------|--------------|--------|
| Latin display + body | **Inter** | 400, 500, 600, 700, 800, 900 | Google Fonts |
| Thai display + body | **Noto Sans Thai** | 400, 500, 600, 700, 800 | Google Fonts |
| Thai fallback | Sarabun | — | (named in stack, optional) |

Mirror the product's import at the top of any HTML mock:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```

Font stack (from `resources/css/app.css`):

```
'Inter', 'Noto Sans Thai', 'Sarabun', ui-sans-serif, system-ui, sans-serif,
'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'
```

> Both families are open-source (OFL) and loaded from CDN, so no `.ttf`/`.woff` files are bundled here.
> If you need offline/self-hosted copies, download Inter (rsms.me/inter) and Noto Sans Thai
> (fonts.google.com) and drop them in this folder.
