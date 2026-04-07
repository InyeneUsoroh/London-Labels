# London Labels UI Brand Style Guide

## Brand Intent
The website visual language should mirror the London Labels logo: modern, premium, vibrant, and clean.

## Core Brand Tokens
Defined in `assets/style.css` under `:root`.

- Primary: `--primary-color` (`#c2186b`)
- Primary dark: `--primary-dark` (`#8e134f`)
- Primary light: `--primary-light` (`#f5b5d6`)
- Secondary: `--secondary-color` (`#ef3f9a`)
- Brand gradient: `--brand-gradient`
- Neutrals: `--dark-color`, `--light-color`, `--border-color`, `--text-primary`, `--text-secondary`

## Usage Rules (Industry Standard)
- Use `--brand-gradient` for high-impact hero and primary conversion surfaces only.
- Use `--primary-color` for primary actions, active states, links, and pagination active states.
- Use `--primary-dark` for emphasized text values (e.g., major prices, key informational emphasis).
- Use semantic colors (`success`, `danger`, `warning`) only for status meaning, not decoration.
- Keep backgrounds mostly neutral; use brand colors as accents.

## Component Standards
- Buttons: primary action uses brand gradient; secondary action uses subtle neutral fill.
- Focus states: all interactive controls use consistent ring (`outline` with brand primary).
- Cards: white background + border token + subtle shadow.
- Pills/Badges: rounded, compact, high contrast, semantic when status-based.
- Nav: all click targets aligned on one line desktop; account dropdown behavior consistent across desktop/mobile.

## Copy & IA Standards
- Prefer standard ecommerce labels:
  - `Support`
  - `Sign In`
  - `Create Account`
  - `My Account`
  - `Sign Out`
- Avoid duplicate links in utility and primary nav for the same destination.

## Accessibility Baseline
- Keep visible focus rings for keyboard users.
- Preserve clear state differentiation (default/hover/active/disabled).
- Do not encode critical meaning using color alone.

## Implementation Notes
- Shared styles are centralized in `assets/style.css`.
- Shared layout and navigation are controlled in `inc_header.php` and `inc_footer.php`.
- Prefer token-based updates over adding hardcoded colors.
