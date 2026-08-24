# ADR 0002: Use Composer classmap autoloading

- Status: Accepted
- Date: 2026-08-21

## Context

The plugin requires generated autoloading, class-based runtime code, and strict WordPress coding standards. WordPress class filenames such as `class-environment-context.php` do not map directly to conventional PSR-4 filenames such as `Environment_Context.php`.

Excluding filename rules would weaken the stated coding-standard baseline. Manual include chains would add ordering concerns and make the entry point responsible for implementation details.

## Decision

Composer builds a classmap from `src/`. Production packages use an optimized autoloader. Runtime files keep WordPress-compatible lowercase, hyphenated names while PHP classes live in the `Akash_Aagrawal\Environment_Guard` namespace.

The main plugin file and uninstall entry point are minimal composition boundaries. All behavior, hook registration, and lifecycle operations remain in classes. The entry point directly loads the class-based dependency guard because that guard must run before Composer is available. The uninstall entry point similarly loads the class-based uninstaller directly only as a recovery fallback when the generated autoloader is missing.

## Consequences

- WordPress file naming and generated autoloading are both preserved.
- Normal runtime startup has no manual source-file include chain; the two explicit lifecycle fallbacks remain dependency-free and class-based.
- A source checkout requires `composer install` before activation.
- A distributable archive must contain the generated production autoloader.
- An incomplete source archive fails activation visibly instead of remaining active without safeguards.
- Adding, removing, or renaming a class requires regenerating Composer's optimized autoloader.
