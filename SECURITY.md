# Security policy

## Supported versions

Security fixes are developed against the latest code on the `main` branch. Once tagged releases exist, this table will identify supported release lines.

| Version | Supported |
| --- | --- |
| Unreleased `main` | Yes |
| Earlier snapshots | No |

## Report a vulnerability

Please do not disclose a suspected vulnerability in a public issue, pull request, discussion, or social-media post.

Use GitHub's private vulnerability-reporting flow at <https://github.com/Akash19jan/wp-environment-guard/security/advisories/new>. Include:

- the affected revision or version;
- the relevant WordPress, PHP, and WooCommerce versions;
- reproduction steps or a minimal proof of concept;
- the security impact;
- whether production or only a guarded environment is affected; and
- any suggested remediation.

Do not include real credentials, email content, customer data, webhook payloads, order data, or production URLs. Replace sensitive values with synthetic examples.

The maintainer will coordinate validation, remediation, and disclosure through the private advisory. Response and release timing depends on severity and reproducibility; no fixed service-level agreement is promised.

## Security boundaries

The following are security-relevant invariants:

- `wp_get_environment_type()` is the only environment authority.
- Production policy decisions are inert.
- Unknown environments normalize to production.
- Runtime guards do not rewrite third-party settings.
- HTTP allowlisting uses exact host matches and disables redirects during enforcement.
- Redirected mail removes copy and reply headers before delivery to the safe sink.
- Policy events, status output, and diagnostics exclude sensitive payloads.

A report showing that one of these invariants can be bypassed is in scope. General WordPress or WooCommerce vulnerabilities, compromised administrator accounts, and failures in infrastructure configuration should be reported to their respective maintainers unless this plugin makes the issue exploitable.
