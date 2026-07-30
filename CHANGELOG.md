# Changelog

All notable changes to this extension are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-30

This release repairs the widget v3 integration that shipped with 1.0.1. Three of the fixes below
address features that were silently doing nothing, so please read the upgrade notes.

### Fixed

- **The widget could not verify at all when TYPO3's frontend CSP was enforced.** ALTCHA v3 runs its
  proof-of-work in Web Workers created from `blob:` URLs. Without an explicit `worker-src` the browser
  falls back to `script-src 'self'`, blocks the worker, and the widget reports
  "Überprüfung fehlgeschlagen" before computing anything. A new
  `Configuration/ContentSecurityPolicies.php` sets `worker-src 'self' blob:` and extends
  `style-src-elem` with `'unsafe-inline'`, which the widget needs for the stylesheet it injects
  inline. Affects every installation with `security.frontend.enforceContentSecurityPolicy` enabled.
- **Custom texts had no effect since the v3 migration.** Widget v3 removed the `strings` attribute, so
  everything rendered by `AltchaTranslations.html` was dropped. The texts are now registered in the
  widget's global i18n store and merged into the resolved locale, which means partial overrides work:
  keys you leave out keep ALTCHA's own translation instead of becoming `undefined`.
- **`verifyUrl` never reached the widget.** Widget v3 has no `verifyurl` attribute; the value has to
  travel inside the `configuration` JSON. Self-hosted setups that configure `verifyUrl` now actually
  perform the server-side verification (see upgrade notes).
- **`minimumComplexity` was ignored.** Challenges were always created with `maximumComplexity`. The
  PBKDF2 cost is now drawn randomly from `[minimumComplexity, maximumComplexity]` per challenge, so
  the workload is no longer predictable.
- **`auto` was always rendered as an attribute**, even with `auto = disabled`. The Fluid condition
  passed the comparison as a quoted string, which is always truthy.
- Missing TCA types for the challenge table.
- `hideLogo` and `hideFooter` are handed to the widget as real booleans instead of TypoScript strings.

### Added

- The v3 display modes (`standard`, `floating`, `overlay`, `bar`, `invisible`) are configurable as
  site settings.

### Changed

- `maximumComplexity` is capped at `50000`. See the upgrade notes for why.
- Widget attributes rendered by the partial are quoted.

### Documentation

- The guide for overriding ALTCHA texts named `partialRootPaths: 20`. This extension registers its
  own partial path as `175`, and Fluid resolves from the highest key downwards — an override with a
  lower key was silently ignored. Corrected to require a key **above 175**.
- Added the `cancel` and `enterCodeFromImage` translation keys, which were missing from the list.
- Documented that widget v3 ships 62 locales (including a complete German one) and resolves the
  language from `<html lang>`, so overriding texts is only needed for different wording — not for
  translations.
- Documented that the i18n registration is global: several widgets on one page share the same texts.

### Upgrade notes

- **Check your `maximumComplexity`.** In widget v1 this value was the counter search range. In v3 it
  is the number of PBKDF2 iterations **per attempt**, and since a challenge needs roughly 256
  attempts, the total work is about 256 times the configured value. A setting of `150000` meant
  around 38 million iterations and pushed the widget into its own 90 second solver timeout on slower
  devices. Values above `50000` are therefore capped. The defaults (`5000`–`15000`) solve in well
  under a second on typical hardware.
- **If you configure `challengeUrl` together with `verifyUrl`,** the server-side verification runs
  from this version on, where it was previously skipped. Your endpoint has to answer with
  `{verified, payload, reason}`; the payload it returns replaces the locally computed one and is
  validated against the HMAC server signature.
- **If you override ALTCHA texts,** raise the `partialRootPaths` key in your YAML above `175`,
  otherwise your partial is ignored.

[1.1.0]: https://github.com/bbysaeth/typo3-altcha/compare/v1.0.1...v1.1.0
