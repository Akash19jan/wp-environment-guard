# ADR 0004: Treat SCSS and accessible HTML as source

- Status: Accepted
- Date: 2026-08-21

## Context

The settings screen and environment indicators communicate safety-critical state. They must remain understandable with assistive technology, keyboard navigation, high zoom, reduced motion, missing styles, or disabled JavaScript.

The project also requires SCSS for authored styles while WordPress consumes browser-ready CSS.

## Decision

All authored styles live under `assets/src/scss/`. The build creates CSS under `assets/build/`; generated CSS is committed for source installations but never edited directly.

Server-rendered HTML provides the complete settings and status experience. JavaScript only enhances conditional presentation. Form controls use explicit labels, related controls use `fieldset` and `legend`, help text is associated with `aria-describedby`, and native semantics are preferred to custom widgets.

Environment state is communicated through text in addition to color. Focus remains visible, contrast targets WCAG 2.2 Level AA, and animation respects `prefers-reduced-motion`.

## Consequences

- The plugin remains operable when JavaScript fails or is disabled.
- Reviewers can distinguish authored SCSS from generated artifacts.
- Asset changes require rebuilding and reviewing both source and output.
- Browser tests can assert semantics and keyboard behavior instead of implementation-specific visual details.
