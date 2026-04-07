# London Labels — UI/UX Governance Blueprint (One-Page)

## 1) Recommended Working Model
- **Primary implementation model:** GPT-5.3-Codex for production code and refactors.
- **Design workflow model:** Token-based design system + reusable page primitives.
- **Delivery model:** Mobile-first, accessibility-first, component-first, then page assembly.

## 2) Product UX Principles (Non-Negotiable)
- **Clarity first:** One primary action per section.
- **Consistency first:** Reuse existing shared patterns before creating new UI.
- **Trust first:** Prominent security, checkout transparency, and policy visibility.
- **Speed first:** Fast load, minimal layout shift, and clean interaction feedback.

## 3) IA and Page Structure Standard
- Use this structure for all major pages:
  1. Shared header/nav
  2. Shared page header (title + concise subtitle + optional actions)
  3. Core task area (form/list/product/detail)
  4. Shared empty-state when no data
  5. Shared pagination when lists exceed one page
  6. Shared footer/legal links
- Keep navigation labels stable and industry-standard (Support, Sign In, Create Account, Sign Out, My Account).

## 4) Visual & Aesthetic Standard
- Preserve current brand direction:
  - Purple hero gradient
  - Blue actions/links
  - White surfaces/cards
  - Red danger states
- Do not introduce ad hoc colors, fonts, or shadows.
- Keep card density moderate: avoid stacking too many micro-messages in a single card.

## 5) Component Reuse Standard
- Always prefer existing shared primitives:
  - `render_page_header(...)`
  - `render_empty_state(...)`
  - `render_pagination(...)`
  - `render_form_input(...)`
  - `render_alert(...)`
- New UI patterns must be added to shared components before page-level usage.

## 6) Microcopy Standard
- Use sentence-case for status labels and stock labels:
  - In stock
  - Out of stock
- Keep messages short and action-focused.
- Avoid duplicate reassurance copy in multiple nearby blocks.
- Keep destructive actions explicit and consistent:
  - Delete
  - “This action cannot be undone.”

## 7) Accessibility Standard (WCAG 2.2 AA)
- Required on all forms and interactive controls:
  - Associated labels
  - `aria-invalid` and `aria-describedby` for errors
  - Keyboard and Escape support for overlays/dropdowns
  - Visible focus states
- Keep semantic heading order and meaningful alt text.

## 8) Performance & Technical Standard
- Prefer shared CSS classes over inline styles.
- Preload critical stylesheet, lazy-load non-critical images.
- Minimize JS to behavior-critical interactions.
- Keep PHP pages server-rendered, predictable, and cache-friendly.

## 9) SEO/Trust/Compliance Standard
- Every public page should maintain:
  - Canonical URL
  - Meta description
  - Open Graph/Twitter metadata
- Keep legal pages linked globally (Privacy, Terms, Cookies, Returns).
- Do not hardcode secrets in code; keep credentials out of repository files.

## 10) Release Quality Gate (Before Merge)
- No diagnostics errors in touched files.
- Mobile + desktop visual spot-check completed.
- Empty-state and validation-state verified.
- Navigation and dropdown interactions verified (click, outside-click, Escape).
- Copy consistency check completed on touched pages.

## 11) Scope Control Rule
- If request says “minimal” or “industry standard,” optimize for fewer UI elements, clearer hierarchy, and reuse of existing primitives.
- Do not add new pages/components unless required by scope.

---

## Practical Implementation Priority
1. Keep simplifying high-traffic commerce pages (Home, Shop, Product, Cart, Checkout).
2. Keep admin list pages aligned to shared header/empty-state/pagination patterns.
3. Keep microcopy and status vocabulary globally consistent.
4. Maintain this blueprint as the source of truth for future UI changes.
