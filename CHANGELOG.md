# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Environment detection backed exclusively by WordPress core.
- Pure, immutable policies for indexing, mail, HTTP requests, WooCommerce payments, and WooCommerce webhooks.
- Runtime-only enforcement that does not rewrite third-party settings.
- Accessible admin and front-end environment indicators.
- Settings, Site Health, and privacy-safe WP-CLI status surfaces.
- Composer classmap autoloading compatible with WordPress file naming.
- SCSS-based asset pipeline with generated distributable CSS.
- PHP unit, JavaScript unit, and browser-level verification scaffolding.
- Adapter regression coverage for WordPress mail, HTTP, indexing, and
  WooCommerce payment and webhook hooks.
- Deterministic release packaging with an installable-ZIP activation smoke
  test and SHA-256 checksum.
- A pre-autoload dependency guard that refuses incomplete installations and
  provides an administration recovery notice.
- Explicit single-site scope with Multisite activation refusal for version
  0.1.
- WordPress, WordPress VIP, PHP compatibility, static analysis, and CI configuration.

### Fixed

- Removed whitespace-prefixed recipient headers from redirected email so a
  folded-header ambiguity cannot preserve `Cc`, `Bcc`, or `Reply-To` values.

[Unreleased]: https://github.com/Akash19jan/wp-environment-guard/commits/main
