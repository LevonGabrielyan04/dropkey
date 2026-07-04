---
name: redesign-existing-projects
description: Upgrades existing PassShare surfaces to premium quality. Audits current design, identifies generic AI patterns, and applies high-end design standards without breaking functionality. Configured for Laravel 13 + Livewire 4 + Flux UI 2 + Tailwind v4 + Alpine.
---

# Redesign Skill

## How This Works

When applied to an existing project, follow this sequence:

1. **Scan** — Read the codebase. Identify the surface (marketing Blade, Livewire app UI, auth), styling method (`resources/css/app.css` with Tailwind v4 `@theme`, `--landing-*` tokens, Flux CSS), and current design patterns.
2. **Diagnose** — Run through the audit below. List every generic pattern, weak point, and missing state you find.
3. **Fix** — Apply targeted upgrades working with the existing stack. Do not rewrite from scratch. Improve what's there.

## PassShare Stack

Work within these boundaries. Do not migrate frameworks or styling libraries.

### Surfaces

| Surface | Location | UI approach |
|---|---|---|
| **Marketing / landing** | `resources/views/welcome.blade.php`, static Blade | Blade + Tailwind utilities + `--landing-*` tokens and classes in `resources/css/app.css` (`.landing-gradient`, `.landing-fade-in`, `.landing-cta-primary`, etc.). CSS-only interactivity preferred. |
| **App UI** | `resources/views/pages/`, `resources/views/dashboard.blade.php` | Livewire 4 MFC components inside Flux layouts (`resources/views/layouts/app/`). Use `<flux:*>` components. `wire:navigate` for in-app navigation. |
| **Auth** | `resources/views/pages/auth/`, `resources/views/layouts/auth/` | Fortify routes + Flux components. Activate `fortify-development` when touching auth. |
| **Shared Blade** | `resources/views/components/` | Anonymous/class components (e.g. `<x-app-logo>`). Follow existing patterns. |

### Core stack

* **Backend:** Laravel 13 on PHP 8.5.
* **Views:** Blade in `resources/views/`.
* **UI components:** Flux UI 2 (free edition) via `<flux:*>` for app surfaces. Do not use Flux Pro components. Do not hand-roll Flux-equivalent components in app UI.
* **Reactivity:** Livewire 4 (`wire:model`, `wire:click`, `wire:loading`). Alpine.js is bundled with Livewire 4 — register components in `resources/js/alpineComponents.js`. **Do NOT** import Alpine separately.
* **Styling:** Tailwind CSS v4 via `@tailwindcss/vite`. **No `tailwind.config.js`** — tokens live in `resources/css/app.css` with `@import 'tailwindcss'`, `@import '../../vendor/livewire/flux/dist/flux.css'`, `@source` directives, and `@theme`. Dark mode: `@custom-variant dark (&:where(.dark, .dark *))`. App UI: `@fluxAppearance` in `resources/views/partials/head.blade.php`.
* **Fonts:** Instrument Sans (weights 400/500/600) via `bunny()` in `vite.config.js`. Output via `@fonts` in Blade head. Never add raw Google Fonts `<link>` tags.
* **Assets:** Vite via `laravel-vite-plugin`. Entry points: `resources/css/app.css`, `resources/js/app.js`, `resources/js/passkeys.js`. App layouts include `@include('partials.head')` and `@include('partials.flux-scripts')`. Run `composer run dev` or `npm run dev` / `npm run build` after frontend changes.
* **Blade optimization:** Livewire Blaze is installed — Flux components are optimized automatically.
* **Animation priority:** (1) CSS `@keyframes` + utility classes, (2) Alpine `x-transition` / `x-show`, (3) CSS scroll-driven `animation-timeline: view()`, (4) GSAP only if already in `package.json`.

### Icons

* **App UI:** `<flux:icon>` / `<flux:button icon="...">` with **Heroicons** (Flux default). Search [heroicons.com](https://heroicons.com/) for exact names.
* **Custom icons:** `php artisan flux:icon crown github` for Lucide icons not in Heroicons. Stored in `resources/views/flux/icon/`.
* **Marketing pages:** Use `<flux:icon>` when Flux scripts are loaded; otherwise Heroicons SVG from the official set. Do not hand-roll icon paths.

### Dependency verification

Before importing any library, check `composer.json` and `package.json`. Flux UI, Livewire, Blaze, and Fortify are Composer packages — verify with `composer.json`, not npm. Never assume a package exists.

### Related skills

Activate alongside this skill — do not improvise patterns they already cover:

* **`fluxui-development`** — `<flux:*>` components, forms, modals, toasts, skeletons.
* **`livewire-development`** — Livewire 4 reactivity, MFC components, `wire:*` directives.
* **`tailwindcss-development`** — Tailwind v4 `@theme`, `@source`, `@custom-variant dark`.
* **`blaze-optimize`** — Blaze component optimization.
* **`fortify-development`** — auth pages and Fortify customization.

## Redesign Modes

Detect the mode before proposing changes:

* **Preserve** — modernise without breaking the brand. Audit first, extract brand tokens, evolve gradually.
* **Overhaul** — new visual language on top of existing content. Treat as greenfield for visuals; preserve content and IA.

If ambiguous, ask once: *"Should this redesign preserve the existing brand, or are we starting visually from scratch?"*

### Preservation rules

* Do not change information architecture unless asked. Keep route names, page slugs, anchor IDs, and primary nav labels stable.
* Extract brand colors from `app.css` `@theme` and `--landing-*` tokens before applying palette changes.
* Preserve copy voice unless asked for a rewrite.
* Honor existing accessibility wins. Do not regress focus states, alt text, keyboard nav, or contrast.
* Never modify without explicit approval: URL structure, primary nav labels, form field names/order, brand logo, legal/consent copy.

## Design Audit

### Typography

Check for these problems and fix them:

- **Browser default fonts or Inter everywhere.** This project uses Instrument Sans. Keep it unless the brief explicitly requires a different stack-wide font. Add new fonts via `bunny()` in `vite.config.js` and `@theme` in `app.css` — never raw CDN `<link>` tags.
- **Headlines lack presence.** Increase size for display text, tighten letter-spacing (`tracking-tighter`), reduce line-height (`leading-none`). Headlines should feel heavy and intentional.
- **Body text too wide.** Limit paragraph width to roughly 65 characters (`max-w-[65ch]`). Increase line-height for readability (`leading-relaxed`).
- **Only Regular (400) and Bold (700) weights used.** Introduce Medium (500) and SemiBold (600) — Instrument Sans supports these weights.
- **Numbers in proportional font.** Use `font-mono` or `tabular-nums` for data-heavy interfaces (dashboards, send counts, timestamps).
- **Missing letter-spacing adjustments.** Use negative tracking for large headers, positive tracking for small caps or labels.
- **All-caps subheaders everywhere.** Try lowercase italics, sentence case, or small-caps instead. Use `<flux:heading>` size variants in app UI.
- **Orphaned words.** Single words sitting alone on the last line. Fix with `text-balance` or `text-pretty`.

### Color and Surfaces

- **Pure `#000000` background.** Replace with off-black or tinted dark. This project uses zinc scale and `--landing-dominant` oklch tokens — stay within the existing palette family.
- **Oversaturated accent colors.** Keep saturation below 80%. Desaturate accents so they blend with neutrals instead of screaming.
- **More than one accent color.** Pick one. Remove the rest. Consistency beats variety. App UI: respect Flux `--color-accent` tokens. Marketing: use `--landing-accent` consistently across all sections.
- **Mixing warm and cool grays.** Stick to one gray family. This project uses zinc (cool-neutral) — do not introduce warm stone/slate alongside it.
- **Purple/blue "AI gradient" aesthetic.** This is the most common AI design fingerprint. Replace with neutral bases and a single, considered accent.
- **Generic `box-shadow`.** Tint shadows to match the background hue. Use colored shadows instead of pure black at low opacity.
- **Flat design with zero texture.** Add subtle noise, grain, or micro-patterns to backgrounds. Marketing pages: extend `.landing-gradient` patterns in `app.css` rather than inline styles.
- **Perfectly even gradients.** Break the uniformity with radial gradients, noise overlays, or mesh gradients instead of standard linear 45-degree fades.
- **Inconsistent lighting direction.** Audit all shadows to ensure they suggest a single, consistent light source.
- **Random dark sections in a light mode page (or vice versa).** A single dark-background section breaking an otherwise light page looks like a copy-paste accident. App UI: use `@fluxAppearance` consistently. Marketing: lock one theme via CSS variables.
- **Empty, flat sections with no visual depth.** Add ambient gradients, subtle patterns, or background imagery. Use `https://picsum.photos/seed/{name}/1920/1080` when real assets are not available.

### Layout

- **Everything centered and symmetrical.** Break symmetry with offset margins, mixed aspect ratios, or left-aligned headers over centered content.
- **Three equal card columns as feature row.** This is the most generic AI layout. Replace with a 2-column zig-zag, asymmetric grid, horizontal scroll, or masonry layout. Use CSS Grid (`grid grid-cols-1 md:grid-cols-2 gap-6`), not flex percentage math.
- **Using `h-screen` for full-screen sections.** Replace with `min-h-[100dvh]` to prevent layout jumping on mobile browsers (iOS Safari viewport bug).
- **Complex flexbox percentage math.** Replace with CSS Grid for reliable multi-column structures.
- **No max-width container.** Add `max-w-7xl mx-auto` or `max-w-[1400px] mx-auto` so content doesn't stretch edge-to-edge on wide screens.
- **Cards of equal height forced by flexbox.** Allow variable heights or use masonry when content varies in length. App UI: use `<flux:card>` with natural height.
- **Uniform border-radius on everything.** Vary the radius: tighter on inner elements, softer on containers.
- **No overlap or depth.** Elements sit flat next to each other. Use negative margins to create layering and visual depth.
- **Symmetrical vertical padding.** Top and bottom padding are always identical. Adjust optically — bottom padding often needs to be slightly larger.
- **Dashboard always has a left sidebar.** This project uses a Flux sidebar layout — improve it, do not rip it out unless asked. Try refining spacing, nav hierarchy, or collapsible sections first.
- **Missing whitespace.** Double the spacing. Let the design breathe. Dense layouts work for data dashboards, not for marketing pages.
- **Buttons not bottom-aligned in card groups.** Pin CTAs to the bottom of each card (`flex flex-col` + `mt-auto` on the button) so they align horizontally.
- **Feature lists starting at different vertical positions.** Use consistent spacing above lists or fixed-height title/price blocks in pricing/comparison cards.
- **Inconsistent vertical rhythm in side-by-side elements.** Align shared elements (titles, descriptions, prices, buttons) across all items.
- **Mathematical alignment that looks optically wrong.** Icons next to text or text in buttons often need 1–2px optical adjustments. Flux icons: standardize stroke width globally.

### Interactivity and States

- **No hover states on buttons.** App UI: use Flux button variants (`variant="primary"`, `variant="ghost"`). Marketing: add `transition-colors duration-200` hover shifts on `.landing-cta-*` classes.
- **No active/pressed feedback.** Add `active:scale-[0.98]` or `active:translate-y-px` on press.
- **Instant transitions with zero duration.** Add `transition-all duration-200` to interactive elements.
- **Missing focus ring.** Flux handles focus on form controls. For custom marketing elements, ensure `focus-visible:ring-2 focus-visible:ring-offset-2`.
- **No loading states.** App UI: use `<flux:skeleton>` and `wire:loading` / `wire:loading.class`. Marketing: skeleton shapes matching layout, not generic spinners.
- **No empty states.** App UI: compose a "getting started" view with `<flux:callout>` or a styled empty panel. Do not show a blank table or list.
- **No error states.** App UI: use `<flux:error name="field" />` with Livewire validation. Never use `window.alert()`. Marketing forms: inline error messages below fields.
- **Dead links.** Use `route()` for named routes. Either link to real destinations or visually disable buttons.
- **No indication of current page in navigation.** App UI: style active nav items in Flux sidebar/navbar. Marketing: highlight current section in nav.
- **Scroll jumping.** Add `scroll-smooth` on `html` for anchor links.
- **Animations using `top`, `left`, `width`, `height`.** Switch to `transform` and `opacity` for GPU-accelerated animation. Prefer CSS keyframes in `app.css` over JS layout animations.

### Content

- **Generic names like "John Doe" or "Jane Smith".** Use diverse, realistic-sounding names.
- **Fake round numbers like `99.99%`, `50%`, `$100.00`.** Use organic, messy data: `47.2%`, `$99.00`, `+1 (312) 847-1928`.
- **Placeholder company names like "Acme Corp", "Nexus", "SmartFlow".** Invent contextual, believable brand names.
- **AI copywriting cliches.** Never use "Elevate", "Seamless", "Unleash", "Next-Gen", "Game-changer", "Delve", "Tapestry", or "In the world of...". Write plain, specific language.
- **Exclamation marks in success messages.** Remove them. App UI: use `<flux:toast>` with calm copy.
- **"Oops!" error messages.** Be direct: "Connection failed. Please try again."
- **Passive voice.** Use active voice: "We couldn't save your changes" instead of "Mistakes were made."
- **All blog post dates identical.** Randomize dates to appear real.
- **Same avatar image for multiple users.** Use unique assets for every distinct person. App UI: `<flux:avatar>` with distinct initials or images.
- **Lorem Ipsum.** Never use placeholder latin text. Write real draft copy.
- **Title Case On Every Header.** Use sentence case instead. `<flux:heading>` in app UI.

### Component Patterns

- **Generic card look (border + shadow + white background).** App UI: use `<flux:card>` and let Flux handle elevation. Marketing: remove redundant borders or use only background color + spacing.
- **Always one filled button + one ghost button.** App UI: `<flux:button variant="primary">` + `<flux:button variant="ghost">` + text links. Reduce visual noise with tertiary styles.
- **Pill-shaped "New" and "Beta" badges.** App UI: use `<flux:badge>`. Try square badges or plain text labels instead of pills.
- **Accordion FAQ sections.** Use a side-by-side list, searchable help, or inline progressive disclosure. Alpine `x-show` for simple toggles on marketing pages.
- **3-card carousel testimonials with dots.** Replace with a masonry wall, embedded social posts, or a single rotating quote.
- **Pricing table with 3 towers.** Highlight the recommended tier with color and emphasis, not just extra height.
- **Modals for everything.** App UI: use `<flux:modal wire:model="showModal">`. Prefer slide-over panels or inline editing for simple actions.
- **Avatar circles exclusively.** App UI: `<flux:avatar>` — try squircles or rounded squares for differentiation.
- **Light/dark toggle always a sun/moon switch.** App UI: settings appearance page (`⚡appearance.blade.php`) with `@fluxAppearance`. Do not add a duplicate toggle unless asked.
- **Footer link farm with 4 columns.** Simplify. Focus on main navigational paths and legally required links.

### Iconography

- **Hand-rolled SVG icons or inconsistent icon sets.** App UI: Heroicons via `<flux:icon>` / `<flux:button icon="...">`. Lucide only via `php artisan flux:icon`. Do not guess icon names — search heroicons.com.
- **Rocketship for "Launch", shield for "Security".** Replace cliche metaphors with less obvious icons (bolt, fingerprint, spark, vault).
- **Inconsistent stroke widths across icons.** Standardize globally (e.g. `stroke-width="1.5"`).
- **Missing favicon.** Favicon lives at `/favicon.png` — update if rebranding.
- **Stock "diverse team" photos.** Use real team photos, candid shots, or a consistent illustration style.

### Code Quality

- **Div soup.** Use semantic HTML: `<nav>`, `<main>`, `<article>`, `<aside>`, `<section>`. App UI: use Flux layout components (`<flux:main>`, `<flux:sidebar>`).
- **Inline styles mixed with CSS classes.** Move styling to Tailwind utilities in Blade or `@layer` rules in `resources/css/app.css`. No `style=""` attributes.
- **Hardcoded pixel widths.** Use relative units (`%`, `rem`, `em`, `max-w-*`) for flexible layouts.
- **Missing alt text on images.** Describe image content for screen readers. Never leave `alt=""` on meaningful images.
- **Arbitrary z-index values like `z-[9999]`.** Establish a clean z-index scale in `@theme` or utility classes.
- **Commented-out dead code.** Remove all debug artifacts before shipping.
- **Import hallucinations.** Check `composer.json` and `package.json` before using any package.
- **Missing meta tags.** Update `resources/views/partials/head.blade.php` with proper `<title>`, `description`, `og:image`, and social sharing meta tags.
- **Raw Alpine import.** Alpine is bundled with Livewire 4. Register custom behavior in `resources/js/alpineComponents.js`, not a separate Alpine install.
- **Bypassing Flux in app UI.** Do not rebuild buttons, inputs, modals, or tables by hand when a `<flux:*>` component exists.

### Strategic Omissions (What AI Typically Forgets)

- **No legal links.** Add privacy policy and terms of service links in the footer.
- **No "back" navigation.** Dead ends in user flows. Every page needs a way back. Use `wire:navigate` or `route()` links.
- **No custom 404 page.** Design a helpful, branded "page not found" experience.
- **No form validation.** App UI: Livewire validation + `<flux:error>`. Marketing: client-side checks for emails and required fields.
- **No "skip to content" link.** Essential for keyboard users. Add a hidden skip-link.
- **No cookie consent.** If required by jurisdiction, add a compliant consent banner.
- **No tests after UI changes.** Run affected Pest tests after backend-touching redesigns. Frontend-only: verify in browser after `npm run build`.

## Upgrade Techniques

When upgrading a project, pull from these high-impact techniques to replace generic patterns:

### Typography Upgrades
- **Variable font animation.** Interpolate weight on scroll or hover for text that feels alive. Use CSS `font-variation-settings` if the font supports it.
- **Outlined-to-fill transitions.** Text starts as a stroke outline and fills with color on scroll entry or interaction.
- **Text mask reveals.** Large typography acting as a window to video or animated imagery behind it.

### Layout Upgrades
- **Broken grid / asymmetry.** Elements that deliberately ignore column structure — overlapping, bleeding off-screen, or offset with calculated randomness.
- **Whitespace maximization.** Aggressive use of negative space to force focus on a single element.
- **Parallax card stacks.** Sections that stick and physically stack over each other during scroll. Use `position: sticky` + CSS, not scroll-bound Livewire state.
- **Split-screen scroll.** Two halves of the screen sliding in opposite directions.

### Motion Upgrades
- **Staggered entry.** Elements cascade in with slight delays via CSS `@keyframes` and `animation-delay`, or Alpine `x-transition` with staggered `x-show`. Never bind animation to Livewire reactive state.
- **CSS scroll-driven reveals.** `animation-timeline: view()` for scroll reveals without JS.
- **Spring physics.** Alpine transitions for UI state changes. GSAP only if already in `package.json`.

### Surface Upgrades
- **True glassmorphism.** `backdrop-filter: blur` + 1px inner border + subtle inner shadow. Provide solid-fill fallback for `prefers-reduced-transparency`.
- **Spotlight borders.** Card borders that illuminate dynamically under the cursor. Alpine `x-on:mousemove` for local UI only — not Livewire state.
- **Grain and noise overlays.** A fixed, `pointer-events-none` overlay with subtle noise to break digital flatness. Add as a utility class in `app.css`.
- **Colored, tinted shadows.** Shadows that carry the hue of the background rather than using generic black.

## Fix Priority

Apply changes in this order for maximum visual impact with minimum risk:

1. **Font and type scale** — biggest instant improvement, lowest risk. Stay within Instrument Sans unless brief says otherwise.
2. **Color palette cleanup** — unify `@theme` and `--landing-*` tokens, remove clashing accents.
3. **Hover and active states** — Flux variants for app UI, utility classes for marketing.
4. **Layout and spacing** — proper grid, max-width container, consistent padding.
5. **Replace generic components** — swap hand-rolled UI for `<flux:*>` in app surfaces.
6. **Add loading, empty, and error states** — `<flux:skeleton>`, `wire:loading`, `<flux:error>`, composed empty views.
7. **Polish typography scale and spacing** — the premium final touch.

## Rules

- Work with the PassShare stack above. Do not migrate frameworks or styling libraries.
- **App UI:** Flux components first. **Marketing:** Blade + Tailwind + `app.css` landing utilities.
- Do not break existing functionality. Test after every change.
- Before importing any library, check `composer.json` and `package.json`.
- Tailwind v4 is CSS-first — tokens in `resources/css/app.css` `@theme`, not `tailwind.config.js`.
- Do not import Alpine separately. Do not use Flux Pro components.
- Use `route()` for links, `wire:navigate` for in-app navigation, `search-docs` before customizing Flux/Livewire behavior.
- Run `vendor/bin/pint --dirty` after PHP changes. Run affected Pest tests when behavior changes.
- Keep changes reviewable and focused. Small, targeted improvements over big rewrites.
- Run `npm run build` or ask the user to run `composer run dev` if UI changes are not visible.
