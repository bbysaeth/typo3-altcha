# TYPO3 Extension `ALTCHA`

This TYPO3 extension integrates ALTCHA Widget v3 into TYPO3 `ext:form`. It uses ALTCHA's proof-of-work approach to protect forms against spam and abuse without visual puzzles or tracking-based captcha techniques.

## Features

- ALTCHA Widget v3 form element for `ext:form`
- Customizable expiration time of challenges
- Local uncached challenge endpoint for cached TYPO3 forms
- Proxy endpoints for self-hosted ALTCHA or Sentinel setups
- Console command and scheduler task for removing obsolete (expired and solved) challenges

## Installation

Install this TYPO3 extension using [Composer](https://getcomposer.org):

```bash
composer require bbysaeth/typo3-altcha
```

Choose one integration method and update the database schema via the install tool:

- **Site Set (recommended, TYPO3 v13.4+)**: include `bbysaeth/typo3-altcha` in your site configuration.
- **Static Template (classic)**: add `Altcha Form Element` in your TypoScript template record.

This TYPO3 extension is licensed under the GNU General Public License Version 2 (GPLv2).

## Configuration

### TypoScript Integration: Site Set or Static Template

This extension supports both TYPO3 integration approaches:

- **Site Set** via `Configuration/Sets/typo3-altcha/`
- **Classic static TypoScript template** via `Configuration/TypoScript/`

Do not load both at the same time. Use one method per site.

### Supported TYPO3 Versions

- `13.4 LTS`
- current `14.x` release line until `14.3 LTS` is available

### Extension Configuration

`HMAC Secret Key (basic.hmac [string])`  
HMAC secret key for challenge generation. If not defined, TYPO3's encryption key will be used.

### TypoScript Configuration Settings

The following TypoScript settings are available:

- `plugin.tx_altcha.minimumComplexity` _(integer)_ – Minimum number for range of complexity
- `plugin.tx_altcha.maximumComplexity` _(integer)_ – Cost used for local `PBKDF2/SHA-256` challenge generation
- `plugin.tx_altcha.expires` _(integer)_ – Seconds after which the challenge expires
- `plugin.tx_altcha.widgetType` _(checkbox, switch, native)_ – Select the widget control style
- `plugin.tx_altcha.display` _(standard, floating, overlay, bar, invisible)_ – Visual layout mode of the widget
- `plugin.tx_altcha.barPlacement` _(bottom, top)_ – Vertical position of the bar, only used with `display = bar`
- `plugin.tx_altcha.hideAltchaLogo` _(bool)_ – Hide the ALTCHA logo in the widget
- `plugin.tx_altcha.hideFooter` _(bool)_ – Hide the ALTCHA footer text and link
- `plugin.tx_altcha.auto` _(Choose: disabled, onload, onfocus, onsubmit)_ – Enable/Disable auto verify onload, onfocus or onsubmit

### Site Set Settings

When you integrate via the site set, the same options are available as site settings. They carry an
`altcha.` prefix instead of the `plugin.tx_altcha.` one, because site settings share a single
namespace across all sets of an installation:

```yaml
# config/sites/<identifier>/settings.yaml
altcha:
  minimumComplexity: 5000
  maximumComplexity: 15000
  widgetType: checkbox
  display: standard
```

> **Renamed in 2.0.0.** These settings previously used bare names (`minimumComplexity` instead of
> `altcha.minimumComplexity`). If you are upgrading from 1.x, rename the keys in your site
> configuration – unprefixed keys are ignored and the settings silently fall back to their defaults.
> Installations using the static template are not affected.

### Form Caching and Challenge Generation

**Important:** This extension automatically uses an uncached endpoint (`/?type=1768669000`) for local challenge generation to prevent form caching issues. This avoids reused challenges in cached forms and keeps ALTCHA Widget v3 compatible with TYPO3 page caching.

No additional configuration is required – the extension handles this automatically.

### Cleaning Up Challenges

Every challenge handed to a client is stored in `tx_typo3altcha_domain_model_challenge`, so that a
solution can only be redeemed once. The row is worthless as soon as the signed expiry has passed – it
only has to outlive the challenge itself. Schedule one of the two options below, otherwise the table
grows with every form view.

**Console command**

```bash
vendor/bin/typo3 altcha:remove-obsolete-challenges [--dry-run] [--include-solved-challenges]
```

Deletes everything older than `plugin.tx_altcha.expires` seconds, so it can be run as often as you
like and honours the configured expiry to the second. `--dry-run` reports what would be deleted
without touching anything, `--include-solved-challenges` additionally removes rows that were already
redeemed. Deletion happens in a single statement, so memory usage does not depend on the number of
rows.

**Core scheduler task**

The extension registers the challenge table with the core's `TableGarbageCollectionTask`, so you can
pick `Table garbage collection` in the scheduler module instead of running the command. This requires
`typo3/cms-scheduler`.

> **Mind the day count.** The registration ships `expirePeriod: 1`, but the core task only uses that
> value when the task is configured to clean up **all** tables. If you select this single table in the
> task, its own *Number of days* field decides instead. Either way the granularity is whole days,
> which is coarser than the command but safe: challenges expire after seconds and the row merely has
> to survive that long.

### Self-hosted ALTCHA Server

You can use a self-hosted Altcha server instead of local challenge generation. Configure the following TypoScript settings:

- `plugin.tx_altcha.challengeUrl` _(string)_ – Challenge endpoint URL passed to the widget as `challenge`
- `plugin.tx_altcha.verifyUrl` _(string)_ – Verification endpoint URL for server-side verification
- `plugin.tx_altcha.apiKey` _(string, optional)_ – API key sent via headers (`Authorization: Bearer` and `X-Altcha-API-Key`)

**Using the Proxy Endpoints (Recommended)**

When both `challengeUrl` and `apiKey` are configured, the extension automatically uses built-in proxy endpoints that:

- Forward requests to your self-hosted server
- Attach the API key via HTTP headers (`Authorization: Bearer {apiKey}` and `X-Altcha-API-Key: {apiKey}`)
- Keep the API key secure (not exposed in frontend HTML)

**Direct URL Mode (Optional)**

If you set only `challengeUrl` without `apiKey`, the widget will connect directly to your server. This is suitable for same-origin servers using session cookies or public endpoints.

**Local Mode (Default)**

If neither `challengeUrl` nor `verifyUrl` are set, the extension uses:

- **Challenge generation**: Uncached endpoint (`/?type=1768669000`) that generates local `PBKDF2/SHA-256` challenges
- **Verification**: Server-side validation in PHP via `AltchaValidator` (no separate verification endpoint required)
- **Benefit**: Prevents form caching issues without requiring `USER_INT` configuration

## Widget v3 Notes

- The extension now uses the widget's `challenge` attribute instead of the removed `challengeurl` or `challengejson` attributes.
- Existing self-hosted integrations can continue to use the TypoScript settings `challengeUrl` and `verifyUrl`; the extension maps these settings to the v3 widget API internally. `verifyUrl` goes into the widget's `configuration` JSON, because v3 has no `verifyurl` attribute.
- Custom texts are no longer passed as a `strings` attribute – that attribute does not exist in v3. The extension renders the `AltchaTranslations` partial into a `data-altcha-i18n` attribute and registers its contents in the widget's global i18n store, see [Customizing ALTCHA Texts](#customizing-altcha-texts).
- The extension exposes `widgetType` and `display` directly and passes `hideAltchaLogo`, `hideFooter` and `barPlacement` through the widget's `configuration` JSON.
- The v3 display modes are available as `plugin.tx_altcha.display`: `standard` (inline), `floating` (bubble, the v2 behaviour), `overlay` (modal), `bar` (narrow bar, new in v3) and `invisible` (new in v3). `bar` can be placed via `plugin.tx_altcha.barPlacement`, which the widget only accepts through the `configuration` JSON.
- For `bar`, `floating` and `overlay` the widget falls back to `auto = onsubmit` on its own when no `auto` mode is configured. `invisible` does **not** get that fallback, so it requires `plugin.tx_altcha.auto` to be set to `onload` or `onsubmit` – otherwise the widget never verifies and the form can never be submitted.
- The extension does not currently expose advanced v3 algorithm configuration for local challenges. The initial local integration targets `PBKDF2/SHA-256`.
- Built-in ALTCHA themes are not wired yet, because they require additional theme CSS assets to be shipped and selected cleanly in TYPO3.

---

## Customizing ALTCHA Texts

> **You probably do not need this for translations.** Widget v3 ships 62 locales, including a
> complete German one (`label: "Ich bin kein Roboter"`). The widget resolves the language from
> `<html lang>`, which TYPO3 fills with the site language, so the widget is translated out of the
> box. Only override texts if you want different wording than ALTCHA's defaults.

The texts are registered in the widget's **global** i18n store. Several widgets on one page
therefore share the same texts – per-widget wording is not supported.

### 1. Create Your Own Partial

Create a new file at the following location in your extension or site package:

```
EXT:my_extension/Resources/Private/Frontend/Partials/AltchaTranslations.html
```

Replace `my_extension` with the key of your sitepackage or custom extension.

### 2. Add YAML Configuration to Register Partial Path

To let TYPO3 know about your new partial path, extend the YAML configuration of the Form Framework. In your sitepackage, add the following file:

**`Configuration/Form/Overrides/form_editor.yaml`**

```yaml
TYPO3:
  CMS:
    Form:
      prototypes:
        standard:
          renderingOptions:
            partialRootPaths:
              200: "EXT:my_extension/Resources/Private/Frontend/Partials/"
```

> **The key matters.** Fluid resolves partials from the highest key downwards, and this extension
> registers its own partial path as `175` (see `Configuration/Yaml/FormSetup.yaml`). Your key must be
> **higher than 175** – with a lower key such as `20` the extension's partial wins and your override
> is silently ignored.

In your TypoScript setup:

```typoscript
plugin.tx_form.settings.yamlConfigurations {
    200 = EXT:my_extension/Configuration/Form/Overrides/form_editor.yaml
}
```

This extension registers its own YAML as `175` from `ext_localconf.php`, so you do not have to repeat
that line – just make sure your key is **higher than 175** so your YAML is loaded afterwards.

---

### Available Translation Keys

You can define any of the following keys inside your `AltchaTranslations.html`. Keys you leave out keep
the value from ALTCHA's own locale, so a partial override is fine:

- `ariaLinkLabel`
- `cancel`
- `enterCode`
- `enterCodeAria`
- `enterCodeFromImage`
- `error`
- `expired`
- `footer`
- `getAudioChallenge`
- `label`
- `loading`
- `reload`
- `verify`
- `verificationRequired`
- `verified`
- `verifying`
- `waitAlert`

---

### Example with Static Texts

**`EXT:my_extension/Resources/Private/Frontend/Partials/AltchaTranslations.html`:**

```html
<f:spaceless>
  <f:format.json
    value="{
        label: 'I am not a robot',
        verified: 'Verified',
        verifying: 'Verifying'
    }"
  />
</f:spaceless>
```

---

### Example with TYPO3 Localization

If you want to use TYPO3’s localization, add the relevant labels to your `locallang.xlf`.

**Partial Example:**

```html
<f:spaceless>
  <f:format.json
    value="{
        ariaLinkLabel: f:translate(key: 'altcha.ariaLinkLabel', extensionName: 'my_extension'),
        error: f:translate(key: 'altcha.error', extensionName: 'my_extension'),
        verified: f:translate(key: 'altcha.verified', extensionName: 'my_extension')
    }"
  />
</f:spaceless>
```

---

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
