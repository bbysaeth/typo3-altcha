# Changelog

All notable changes to this extension are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - Unreleased

### Fixed

- **The internal endpoints ignored the site language.** The challenge and proxy endpoints were written
  out as root-relative `/?type=…` URLs. On a site whose languages all use a path prefix (`/de/`,
  `/en/`, nothing on `/`) there is no page on `/`, so the request was redirected to the default
  language and answered with the start page's HTML instead of JSON — the widget could neither load nor
  verify a challenge. All three endpoints are now generated with `f:uri.page` for the current
  language. Single-language sites are unaffected, the URL stays `/?type=…`. Reported and diagnosed by
  @ronykhoury in #56.

### Changed

- Updated the ALTCHA widget from 3.0.10 to 3.2.1. The parts this extension builds on are unchanged –
  the custom element's props, the configuration keys, the payload format and the global i18n store API
  are all identical – so this is a drop-in update. What it brings:
  - `customElements.define('altcha-widget', …)` is now guarded by `!customElements.get(…)`, which
    prevents a duplicate definition error when the bundle ends up on a page twice.
  - A Safari-only fix for the checkbox focus outline.
  - Updated translations across the bundled locales.
  - Nonce support for the stylesheet the widget injects: it copies
    `document.currentScript.nonce` and falls back to `meta[name="csp-nonce"]`. This is not usable
    under TYPO3 yet, see below.

### Notes

- `style-src-elem 'unsafe-inline'` is still required despite the widget's new nonce support. TYPO3
  only nonces inline assets, not `<script src="…">`, so the extension's bundle carries no nonce for
  the widget to copy, and rendering a `meta[name="csp-nonce"]` by hand would break on cached pages
  because the nonce is per request while the markup is not. The reasoning is documented in
  `Configuration/ContentSecurityPolicies.php` so the next reader does not have to re-derive it.
- `altcha-org/altcha` (the PHP library) stays on `^2.0`. There is no 3.x of that package – it is a
  different project from the npm widget, and v2.1.0 is the current maximum, which `^2.0` already
  covers.

## [2.0.0] - 2026-07-30

### Changed

- **BREAKING: the site set settings are now prefixed with `altcha.`.** They previously used bare names
  such as `minimumComplexity` or `widgetType`. Site settings share a single namespace across every set
  of an installation, so those names could collide with other extensions.

  Rename the keys in your site configuration. Unprefixed keys are ignored, and the affected settings
  silently fall back to their defaults:

  | before | after |
  | --- | --- |
  | `minimumComplexity` | `altcha.minimumComplexity` |
  | `maximumComplexity` | `altcha.maximumComplexity` |
  | `expires` | `altcha.expires` |
  | `auto` | `altcha.auto` |
  | `widgetType` | `altcha.widgetType` |
  | `display` | `altcha.display` |
  | `barPlacement` | `altcha.barPlacement` |
  | `hideAltchaLogo` | `altcha.hideAltchaLogo` |
  | `hideFooter` | `altcha.hideFooter` |
  | `challengeUrl` | `altcha.challengeUrl` |
  | `verifyUrl` | `altcha.verifyUrl` |
  | `apiKey` | `altcha.apiKey` |
  | `jsonContentTypeHeader` | `altcha.jsonContentTypeHeader` |

  **Installations using the static template are not affected** – those are TypoScript constants under
  `plugin.tx_altcha` and were never renamed. The category names already carried a `typo3altcha`
  prefix and are unchanged.

  TYPO3 offers no aliasing or deprecation mechanism for renamed site settings, so there is no
  transitional period: the old keys stop working with this release.

### Documentation

- Documented the site set settings in the README, which previously only listed the
  `plugin.tx_altcha.*` TypoScript constants.

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
- **The cleanup command ran out of memory on large tables.** It loaded every challenge as an Extbase
  object and filtered them in PHP, keeping each row plus its persistence session entry in memory.
  Counting and deleting now happen in a single statement each. Measured with 300 000 obsolete rows and
  `memory_limit=128M`: the old code died with `Allowed memory size of 134217728 bytes exhausted`, the
  new one deletes them in 1.4 s. Reported by a user who hit this in production.
- Hidden challenge rows are now cleaned up as well. The Extbase query applied the default enable
  fields, so rows with `hidden = 1` were never removed.
- Missing TCA types for the challenge table.
- `hideLogo` and `hideFooter` are handed to the widget as real booleans instead of TypoScript strings.

### Added

- The v3 display modes (`standard`, `floating`, `overlay`, `bar`, `invisible`) are configurable as
  site settings.
- The challenge table is registered with the core's `TableGarbageCollectionTask`, so cleaning up can
  be scheduled from the scheduler module instead of the extension's console command. Note that the
  shipped `expirePeriod` only applies when the task cleans up all tables; selecting this single table
  makes the task's own *Number of days* field decide.

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

[2.0.0]: https://github.com/bbysaeth/typo3-altcha/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/bbysaeth/typo3-altcha/compare/v1.0.1...v1.1.0
