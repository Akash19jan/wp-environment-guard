# ADR 0001: Use WordPress as the environment authority

- Status: Accepted
- Date: 2026-08-21

## Context

Safety controls are only useful when environment classification is predictable. Hostname heuristics, database flags, and editable plugin settings can become stale when a database is copied or a domain changes. They can also classify an unfamiliar host as non-production and accidentally alter production traffic.

WordPress core provides `wp_get_environment_type()` and recognizes `local`, `development`, `staging`, and `production`. Its default is `production`.

## Decision

The environment detector delegates to `wp_get_environment_type()`. It records whether WordPress obtained the value from `WP_ENVIRONMENT_TYPE`, an environment variable, or its default for diagnostics, but it does not create another source of truth.

The settings UI cannot select an environment. Unknown values normalize to `production`. Every policy asks the immutable environment context whether it is guarded before considering plugin settings. Production and emergency-disabled contexts return an allow decision.

The constant `AKASH_AAGRAWAL_ENVIRONMENT_GUARD_DISABLED` is an emergency kill switch. It disables enforcement without changing the detected environment or stored settings.

## Consequences

- Environment configuration stays in deployment configuration rather than the database.
- A copied database cannot silently make a production site behave like staging.
- Missing and unknown values fail to WordPress's inert production behavior.
- Local teams must configure `WP_ENVIRONMENT_TYPE` correctly before expecting protections.
- The kill switch is visible to diagnostics and must not become routine configuration.
