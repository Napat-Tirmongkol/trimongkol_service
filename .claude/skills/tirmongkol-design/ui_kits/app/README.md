# App UI Kit — Homework Scanner

An interactive, click-through recreation of the **Homework Scanner** SaaS (the authenticated product).
Recreated from `resources/views/{layouts/navigation, classrooms, assignments, auth}` — not screenshots.

## Run
Open `index.html`. Walk the real flow:
**Login** (email-first → password) → **Dashboard** (classrooms) → **Classroom** (students + assignments)
→ **Assignment** (submission tracking) → **Scan** (type a code to mark a student submitted).

On the Scan screen, type a real code — e.g. `P5K2C9` (Chai) or `T9L4E2` (Fern) — and press OK to see the
success toast, the live count tick up, and the student appear in "Recently submitted." Unknown codes and
duplicates raise the matching error toast.

## Files
| File | What's in it |
|------|--------------|
| `index.html` | App shell + screen state machine |
| `icons.jsx` | Shared `Icon` set (same as the website kit) |
| `app-shell.jsx` | Tokens (`window.AT`), `TopNav`, `AButton`, `Card`, `StatCard`, `CategoryBadge`, `Toast`, `PageHeader` |
| `screens.jsx` | `LoginScreen`, `Dashboard`, `ClassroomScreen`, `AssignmentScreen`, `ScanScreen` + sample data (`window.APP_DATA`) |

## Components & patterns covered
- **Unified auth** — email-first sign-in (identify → password steps), `slate-900` primary button,
  centered card on the guest layout chrome.
- **In-app TopNav** — sticky `white/95 backdrop-blur`, "My Classrooms" active pill (`bg-slate-900`),
  TH/EN toggle, avatar dropdown (gradient initials + menu).
- **Dashboard** — greeting (`Hi, Ploy 👋`), three stat cards, classroom rows (hover lift), "Get started"
  numbered checklist.
- **Stat cards** — tinted icon tile (brand / amber / emerald) + uppercase label + tabular number.
- **Classroom** — page-header band with grade badge, four quick-stat/action tiles (incl. the
  gradient "Add Assignment" tile), assignments list (category badge + scoring-mode pill), students
  table with monospace barcode codes and QR · View · Edit actions.
- **Assignment** — submission progress card (brand progress bar), mode + average card, scan CTA;
  per-student status (submitted pill / pending), score, time, Undo / Mark submitted.
- **Scan** — full dark screen, QR viewfinder with brand corner frame, big live count, manual code
  entry, success/error toasts, "Recently submitted" feed.

## Copy & data
Strings are verbatim from `lang/en/app.php` (dashboard, scan, assignments, students, auth). Sample
students/assignments are illustrative; scoring modes (Check only / Quick score / Custom score) and
categories match the product's options.

## Known simplifications
- Cosmetic recreation: no backend, real camera, CSV export, gradebook, workspaces, plans, or admin
  portal — those exist in the product but are out of scope for the kit's core-screen coverage.
- Sample classroom is "Grade 5/2 English" with 8 students for legibility.
