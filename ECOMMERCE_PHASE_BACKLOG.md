# London Labels — Ecommerce Feature Backlog (Industry-Standard Roadmap)

## How to Use This Backlog
- Prioritize by **Impact** (conversion, retention, trust) and **Complexity**.
- Implement in phases to avoid destabilizing core checkout flows.
- Each item includes likely touchpoints for quick implementation planning.

---

## Phase 1 — High Impact / Low-Medium Complexity

### 1) Predictive Search Suggestions
- **Goal:** Improve product discovery speed from header/shop search.
- **Impact:** High
- **Complexity:** Medium
- **Files:** `inc_header.php`, `shop.php`, `assets/style.css`, new endpoint (e.g. `search_suggest.php`), `db_functions.php`
- **Acceptance:** Typing 2+ chars shows top matching products/categories with keyboard support.

### 2) Stronger Catalog Filters (Price Range + Existing Filters)
- **Goal:** Let users narrow products quickly by budget + availability.
- **Impact:** High
- **Complexity:** Low-Medium
- **Files:** `shop.php`, `db_functions.php`
- **Acceptance:** `min_price`/`max_price` persist across category/sort/pagination and return correct totals.

### 3) Wishlist (MVP)
- **Goal:** Save products for later and improve return visits.
- **Impact:** High
- **Complexity:** Medium
- **Files:** `schema.sql`, `db_functions.php`, `shop.php`, `product.php`, new `account/wishlist.php`
- **Acceptance:** Logged-in users can add/remove items and view wishlist page.

### 4) Reviews Placeholder Model (Scaffold)
- **Goal:** Add social-proof foundation without full moderation complexity yet.
- **Impact:** Medium-High
- **Complexity:** Medium
- **Files:** `schema.sql`, `product.php`, `db_functions.php`
- **Acceptance:** Product page can display review summary block and “Reviews coming soon” state.

---

## Phase 2 — Conversion + Post-Purchase Trust

### 5) Promo/Coupon Engine (Basic)
- **Goal:** Support campaign-based conversions.
- **Impact:** High
- **Complexity:** Medium
- **Files:** `schema.sql`, `checkout.php`, `cart.php`, `db_functions.php`, `admin/*`
- **Acceptance:** Valid coupon applies discount and displays transparent savings in cart/checkout.

### 6) Delivery ETA Messaging
- **Goal:** Reduce uncertainty and improve purchase confidence.
- **Impact:** Medium-High
- **Complexity:** Medium
- **Files:** `product.php`, `checkout.php`, `config.php`
- **Acceptance:** ETA shown consistently using configurable shipping windows.

### 7) Reorder and Invoice Download
- **Goal:** Improve repeat purchase and account utility.
- **Impact:** Medium
- **Complexity:** Medium
- **Files:** `account/orders.php`, `order-confirmation.php`, new invoice endpoint
- **Acceptance:** One-click reorder adds prior order items to cart; invoice file can be downloaded.

### 8) Returns Center (Self-Serve)
- **Goal:** Bring post-purchase flow to industry baseline.
- **Impact:** Medium
- **Complexity:** Medium-High
- **Files:** new account returns pages, `schema.sql`, `admin/orders.php`
- **Acceptance:** Customer can submit return request and track status.

---

## Phase 3 — Optimization / Scale

### 9) Analytics Event Model
- **Goal:** Measure funnel drop-off and feature performance.
- **Impact:** High
- **Complexity:** Medium
- **Files:** `inc_header.php`, `cart.php`, `checkout.php`, `product.php`
- **Acceptance:** Standardized events for view/search/add-to-cart/checkout/purchase.

### 10) Recommendation Modules
- **Goal:** Increase AOV via related/recently viewed recommendations.
- **Impact:** Medium-High
- **Complexity:** Medium-High
- **Files:** `product.php`, `index.php`, `db_functions.php`
- **Acceptance:** Personalized or heuristic recommendations render without slowing page.

### 11) Localization Controls (Currency/Country)
- **Goal:** Improve cross-market readiness.
- **Impact:** Medium
- **Complexity:** High
- **Files:** `config.php`, `inc_header.php`, pricing display helpers
- **Acceptance:** Currency formatting and country context update consistently sitewide.

---

## Standards Guardrails (Apply to Every Backlog Item)
- Reuse shared primitives (`render_page_header`, `render_empty_state`, `render_pagination`, `render_form_input`).
- Keep copy concise and sentence-case.
- Preserve accessibility (labels, focus, keyboard, aria states).
- No hardcoded secrets; environment-driven config only.
- Validate touched files with diagnostics before completion.
