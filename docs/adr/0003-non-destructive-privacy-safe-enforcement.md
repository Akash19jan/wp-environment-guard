# ADR 0003: Keep enforcement non-destructive and privacy-safe

- Status: Accepted
- Date: 2026-08-21

## Context

Staging protections often change third-party plugin options, disable plugins, or retain full outbound payloads for troubleshooting. Those approaches create two risks: configuration changes can be copied back to production, and logs can accumulate personal data, secrets, internal endpoints, or payment identifiers.

Environment safety also needs observability. Operators must be able to understand which policy acted without exposing the data that policy protected.

## Decision

Policies return immutable decisions with stable action and reason codes. Hook adapters enforce those decisions at runtime through public WordPress and WooCommerce APIs.

The plugin does not rewrite gateway test-mode options, change another plugin's activation state, disable scheduled tasks, or change WordPress's environment value. Its pre-autoload dependency guard may self-deactivate this plugin after an incomplete update; this prevents a broken copy from appearing protected. Uninstall removes only the plugin's own option.

The plugin does not persist event history. Its policy event contains only:

- policy identifier;
- action identifier; and
- stable reason code.

It never includes message-derived email addresses, subjects, message bodies, attachment paths, full URLs, query strings, request headers, response bodies, webhook payloads, order identifiers, or payment data. The administrator-configured safe sink is stored only as plugin configuration and is excluded from events, status, Site Health, and WP-CLI output.

## Consequences

- Production configuration cannot be damaged by restoring a guarded database.
- Operators can subscribe to low-cardinality policy decisions without receiving payload data.
- Detailed request replay and message inspection are deliberately outside scope.
- Integrations preserve earlier terminal blocks or interception results when doing so cannot weaken an active safeguard. Indexing protection intentionally overrides an earlier public-indexing value with zero.
- Any future audit feature requires a separate privacy and retention decision before implementation.
