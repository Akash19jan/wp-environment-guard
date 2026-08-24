# Contributing

Thank you for improving WP Environment Guard. Changes should preserve its central promise: non-production safeguards must be predictable, non-destructive, and inert on production.

## Local setup

Requirements:

- PHP 8.2 or newer
- Composer 2
- Node.js 22 and npm
- Docker for the optional `wp-env` browser-test environment

Install dependencies from the repository root:

```bash
composer install
npm ci
```

## Design rules

- Put runtime behavior in namespaced classes. Keep entry points limited to bootstrapping and composition.
- Prefer constructor injection and small interfaces. Do not introduce singletons, global registries, or a service locator.
- Keep policy decisions pure. WordPress and WooCommerce hooks belong in adapter classes.
- Ask the immutable environment context whether enforcement may run before evaluating configurable behavior.
- Treat production as inert. Do not add a production override in the settings UI.
- Do not infer an environment from a URL, hostname, database value, or plugin state.
- Do not mutate another plugin's options, activation state, gateway mode, or scheduled tasks.
- Preserve an earlier terminal short-circuit value supplied by another plugin when it already prevents the external side effect. Never preserve a value that weakens an active safeguard.
- Never persist or emit message-derived email content, customer recipient addresses, attachment paths, full URLs, query strings, headers, request or response bodies, webhook payloads, order identifiers, credentials, or tokens. An administrator-configured safe-sink address may be stored as plugin configuration but must not appear in policy events or diagnostics.
- Use exact normalized host matching. Wildcards, substring matching, and implicit subdomain matching require a separate architecture decision.
- Prefix WordPress globals, hook names, option names, script handles, style handles, and DOM classes with the project prefix.

If a change alters one of these boundaries, add or supersede an architecture decision record in `docs/adr/`.

## PHP

PHP must pass the complete WordPress Coding Standards ruleset, WordPress VIP rules, PHP compatibility checks, PHPStan, and PHPUnit.

Class files follow WordPress naming, for example `class-mail-policy.php`. Composer's classmap bridges that convention to generated autoloading. Do not replace it with manual includes or exclude production source from filename checks.

Run:

```bash
composer check
```

Tests should verify observable decisions and invariants. Prefer real value objects and small fakes over mocking implementation details. A bug fix should include a regression test that fails before the fix.

## JavaScript and SCSS

JavaScript is progressive enhancement. Settings must remain visible, understandable, and submittable without it.

SCSS under `assets/src/scss/` is the only authored style source. Never edit `assets/build/*.css` directly. Rebuild assets after source changes:

```bash
npm run check
```

Use native HTML semantics before adding ARIA. Verify keyboard access, visible focus, text alternatives, zoom/reflow, contrast, non-color status cues, and reduced-motion behavior against WCAG 2.2 Level AA.

## Integration tests

Start a disposable staging environment and run the browser suite:

```bash
npm run env:start
npm run test:e2e
npm run env:stop
```

Do not run end-to-end tests against a real site. The suite is designed for the repository's disposable `wp-env` configuration.

## Pull requests

Keep each pull request focused and explain the safety property it preserves. Before requesting review:

- [ ] The change has no effect on production unless it is a read-only status surface.
- [ ] No third-party setting or plugin activation state is mutated.
- [ ] No sensitive value is logged, persisted, or included in CLI/Site Health output.
- [ ] New behavior is represented by a pure policy decision where practical.
- [ ] PHP and front-end checks pass locally.
- [ ] Generated assets are rebuilt from SCSS or JavaScript source.
- [ ] User-facing text is translatable and accessible.
- [ ] Documentation and the changelog are updated.
- [ ] No `vendor/`, `node_modules/`, test report, credential, or local environment file is committed.

Automated checks support review; they do not replace a security, privacy, accessibility, or production-inertness review.
