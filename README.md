# WP Environment Guard

WP Environment Guard applies explicit, non-destructive safety policies to local, development, and staging WordPress environments. Production remains inert by design.

The plugin is intended for teams that need predictable protection against accidental indexing, outbound email, external HTTP requests, live payment gateways, and WooCommerce webhook delivery when a database is copied away from production.

## Safety model

WordPress is the only authority for the current environment. The plugin reads `wp_get_environment_type()` and does not infer an environment from a hostname, URL, database value, or admin setting.

| Concern               | Production   | Non-production default                  |
| --------------------- | ------------ | --------------------------------------- |
| Search indexing       | Pass through | Block indexing and sitemaps             |
| WordPress mail        | Pass through | Short-circuit as successfully handled   |
| WordPress HTTP API    | Pass through | Observe without storing request data    |
| WooCommerce payments  | Pass through | Expose only `bacs`, `cheque`, and `cod` |
| WooCommerce webhooks  | Pass through | Block delivery                          |
| Environment indicator | No guard UI  | Show an admin and front-end indicator   |

The non-production policies can be adjusted at **Settings > Environment Guard**. The settings screen cannot select or change the WordPress environment.

## Design guarantees

- Production requests are allowed without changing their arguments or third-party settings.
- An unknown environment value normalizes to WordPress's production default.
- The emergency-disable constant stops guard enforcement without changing stored configuration.
- Payment policies filter runtime gateway availability; they never toggle a gateway's live or test mode.
- HTTP allowlisting compares exact normalized hosts. It does not use substring or suffix matching.
- HTTP redirects are disabled while allowlist enforcement is active so an allowed host cannot redirect to an unapproved destination.
- Blocked mail is short-circuited before delivery. Redirected mail removes `Cc`, `Bcc`, and `Reply-To` headers before using the configured safe sink.
- Policy events contain only a policy name, action, and stable reason code. They never log or persist message-derived addresses, subjects, bodies, attachment paths, URLs, query strings, headers, webhook payloads, or order identifiers. The administrator-configured safe sink remains ordinary plugin configuration and is never included in events or diagnostics.
- The plugin does not activate or deactivate other plugins, modify gateway settings, disable cron, or intercept transport libraries outside WordPress APIs.

See the [architecture decision records](docs/adr/) for the reasoning behind these boundaries.

## Requirements

- WordPress 6.5 or newer
- PHP 8.2 or newer
- A single-site WordPress installation. Version 0.1 intentionally refuses
  activation on WordPress Multisite while network-level settings semantics are
  out of scope.
- Composer 2 for a source checkout
- Node.js 22 and npm for asset development only
- WooCommerce only when payment or webhook protections are needed

## Installation

### Packaged release (recommended)

Download the versioned `wp-environment-guard-X.Y.Z.zip` file from the GitHub release's **Assets** section. Install that file in **Plugins > Add New > Upload Plugin**, or with WP-CLI:

```bash
wp plugin install /path/to/wp-environment-guard-X.Y.Z.zip --activate
```

The versioned release asset is the installable plugin. It contains the production Composer autoloader, runtime PHP source, and compiled assets under one `wp-environment-guard/` directory.

> [!IMPORTANT]
> GitHub's automatically generated **Source code (zip)** and **Source code (tar.gz)** downloads are source snapshots, not installable plugin packages. They do not contain `vendor/autoload.php`. Use the separately named versioned ZIP from release assets, or complete the source installation steps below.

Verify the adjacent checksum before deployment when your release process supports it:

```bash
sha256sum --check wp-environment-guard-X.Y.Z.zip.sha256
```

### Source checkout

Clone the repository into `wp-content/plugins/wp-environment-guard`, install the optimized Composer autoloader, and activate the plugin:

```bash
composer install --no-dev --optimize-autoloader
wp plugin activate wp-environment-guard
```

Compiled assets under `assets/build/` are distributable build artifacts and are committed intentionally. Source-checkout consumers do not need Sass or Node.js. The release workflow copies those assets and the optimized production autoloader into its explicit package allowlist.

Do not activate a source checkout until Composer installation has completed. A missing autoloader is treated as an unmet dependency and activation is refused.

An incomplete copy without `vendor/autoload.php` cannot be activated. If an
update removes that file from an active installation, the plugin deactivates
itself on the next administration request and displays a recovery notice.

## Configure the environment

Define the environment outside the database, normally in `wp-config.php` or platform configuration:

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' );
```

WordPress supports `local`, `development`, `staging`, and `production`. When the constant is absent, WordPress defaults to `production`, so the guard remains inert.

For incident recovery only, policy enforcement can be disabled without changing saved settings:

```php
define( 'AKASH_AAGRAWAL_ENVIRONMENT_GUARD_DISABLED', true );
```

Treat this constant as a temporary kill switch. Remove it after the incident and investigate why a guarded policy had to be bypassed.

## Policy configuration

Open **Settings > Environment Guard** to configure the following non-production behavior:

- Front-end environment indicator
- Search indexing protection
- Mail mode: block, redirect to one safe address, or allow
- HTTP mode: observe or enforce an exact-host allowlist
- WooCommerce payment mode: block, allowlist, or allow
- WooCommerce webhook blocking

Every setting is progressively enhanced. The form remains usable without JavaScript, and status is conveyed with text rather than color alone.

## Command line and Site Health

Inspect the effective environment and privacy-safe policy state:

```bash
wp environment-guard status
```

Check safety invariants in scripts and deployment pipelines:

```bash
wp environment-guard check
```

`check` exits non-zero when a non-production environment has unsafe or emergency-disabled safeguards. An inert production environment is healthy. Neither command outputs destinations, URLs, headers, payloads, or other sensitive values.

The same high-level state is exposed through WordPress Site Health without including policy payloads.

## Architecture

Runtime code is class-based and divided by responsibility:

```text
src/
├── admin/         Settings and administration adapters
├── cli/           WP-CLI registration and commands
├── contracts/     Small integration contracts
├── core/          Composition root and plugin context
├── environment/   WordPress environment detection
├── frontend/      Public environment indicator
├── integration/   WordPress and WooCommerce hook adapters
├── lifecycle/     Activation and uninstall behavior
├── policy/        Pure policy decisions
├── settings/      Typed settings and sanitization
└── support/       Small reusable collaborators
```

The plugin entry point is a composition boundary. It loads Composer and delegates registration to classes. Dependencies are passed explicitly rather than obtained through a service locator or singleton.

Composer uses an optimized classmap for `src/`. This preserves the strict WordPress class/file naming convention while retaining generated autoloading and avoiding manual `require_once` chains. The rationale is documented in [ADR 0002](docs/adr/0002-use-composer-classmap-autoloading.md).

## Development

Install development dependencies:

```bash
composer install
npm ci
```

Run the PHP quality suite:

```bash
composer check
```

Run JavaScript, Sass, package metadata, unit tests, and a production asset build:

```bash
npm run check
```

Start the disposable WordPress environment and run browser tests:

```bash
npm run env:start
npm run test:e2e
npm run env:stop
```

SCSS in `assets/src/scss/` is the source of truth. Do not edit generated CSS in `assets/build/` by hand. JavaScript is progressive enhancement and must not be required to save or understand settings.

No command is documented here as having passed on a particular checkout. Verify the current branch locally or review its latest CI result.

## Release packaging

The `Release package` GitHub Actions workflow is the only supported release packager. A manual run builds and smoke-tests an Actions artifact without publishing a GitHub release. Pushing a version tag also creates or updates the corresponding GitHub release and attaches the installable ZIP and SHA-256 checksum.

Before tagging a release:

1. Set the same semantic version in `package.json`, the `Version` header in
   `wp-environment-guard.php`, and `Plugin::VERSION` in
   `src/core/class-plugin.php`.
2. Update `CHANGELOG.md` and commit generated files under `assets/build/`.
3. Push an exact matching tag such as `v0.1.0`.

The workflow runs the complete PHP and asset quality suites, prunes Composer to `--no-dev` dependencies with an authoritative optimized classmap, and assembles an explicit package allowlist. It normalizes file permissions and timestamps, sorts archive entries, tests the ZIP, verifies the autoloader and required files, and rejects development-only paths. Repository documentation referenced by the packaged README is included.

The final gate extracts the ZIP into an isolated `wp-env` project, activates that extracted plugin on PHP 8.2, and runs the Environment Guard deployment check. A failed activation or check prevents artifact publication. Tag builds publish the exact ZIP that passed this smoke test; GitHub's automatic source archives remain development inputs only.

## Coding and accessibility standards

Contributions are checked against:

- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress HTML Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [WordPress VIP Coding Standards](https://github.com/Automattic/VIP-Coding-Standards)
- [WCAG 2.2 Level AA](https://www.w3.org/TR/WCAG22/)

See [CONTRIBUTING.md](CONTRIBUTING.md) for the review checklist.

## Security

Do not report security issues in a public issue. Follow [SECURITY.md](SECURITY.md) to submit a private report.

## License

WP Environment Guard is licensed under the GNU General Public License, version 2 or later. See [LICENSE](LICENSE).
