# TYPO3 Extension `Altcha`

This TYPO3 extension integrates Altcha, an innovative alternative to traditional captchas, into the Form Extension. Altcha employs a proof-of-work approach to safeguard forms against spam and abuse without requiring users to solve visual puzzles.

Key Features:

- Seamless integration with the TYPO3 Form Extension
- Configurable difficulty levels for the proof-of-work mechanism
- Automatic validation of Altcha responses
- Enhanced protection against automated bots
- User-friendly alternative to conventional captchas

The extension empowers developers to easily incorporate Altcha into existing forms, thereby enhancing security without compromising user experience.

## Features

- Altcha spam protection field for ext:form
- Customizable expiration time of challenges
- Scheduler task for removing obsolete (expired and solved) challenges

## Installation

Install this TYPO3 extension using [Composer](https://getcomposer.org):

```bash
composer require bbysaeth/typo3-altcha
```

Add the static template and update the database schema via the install tool.

This TYPO3 extension is licensed under the GNU General Public License Version 2 (GPLv2).

## Configuration

### Extension Configuration

`HMac Sercret Key (basic.hmac [string])`  
HMAC secret key for challenge generation. If not defined, TYPO3's encryption key will be used.

### TypoScript Configuration Settings

The following TypoScript settings are available:

- `plugin.tx_altcha.minimumComplexity` _(integer)_ – Minimum number for range of complexity
- `plugin.tx_altcha.maximumComplexity` _(integer)_ – Maximum number for range of complexity (must be larger than minimumComplexity)
- `plugin.tx_altcha.expires` _(integer)_ – Seconds after which the challenge expires
- `plugin.tx_altcha.hideFooter` _(bool)_ – Hide/Show Altcha footer link in field
- `plugin.tx_altcha.hideAltchaLogo` _(bool)_ – Hide/Show Altcha logo in field
- `plugin.tx_altcha.auto` _(Choose: disabled, onload, onfocus)_ – Enable/Disable auto verify onload or onfocus

### Form Caching and Challenge Generation

**Important:** This extension automatically uses an uncached endpoint (`/?type=1768669000`) for local challenge generation to prevent form caching issues. This solves the common problem where cached forms reuse the same challenge, causing validation failures on the first submit attempt.

No additional configuration is required – the extension handles this automatically.

### Self-hosted Altcha Server

You can use a self-hosted Altcha server instead of local challenge generation. Configure the following TypoScript settings:

- `plugin.tx_altcha.challengeUrl` _(string)_ – Challenge endpoint URL
- `plugin.tx_altcha.verifyUrl` _(string)_ – Verification endpoint URL (required for server-side verification)
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

- **Challenge generation**: Uncached endpoint (`/?type=1768669000`) that generates challenges via HMAC
- **Verification**: Server-side validation in PHP via `AltchaValidator` (no `verifyurl` needed)
- **Benefit**: Prevents form caching issues without requiring `USER_INT` configuration

---

## 🧩 Customizing Altcha Widget Texts

### 🛠 1. Create Your Own Partial

Create a new file at the following location in your extension or site package:

```
EXT:my_extension/Resources/Private/Frontend/Partials/AltchaTranslations.html
```

Replace `my_extension` with the key of your sitepackage or custom extension.

### 🛠 2. Add YAML Configuration to Register Partial Path

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
              20: "EXT:my_extension/Resources/Private/Frontend/Partials/"
```

> Your YAML file must be included in TypoScript with a key **higher than the one used by this extension (e.g. > 125)** to ensure it overrides the default path.

In your TypoScript setup:

```typoscript
plugin.tx_form.settings.yamlConfigurations {
    125 = EXT:altcha/Configuration/Yaml/FormSetup.yaml
    200 = EXT:my_extension/Configuration/Form/Overrides/form_editor.yaml
}
```

This ensures that your own YAML is loaded **after** the one provided by Altcha.

---

### 🔤 Available Translation Keys

You can define any of the following keys inside your `AltchaTranslations.html`:

- `ariaLinkLabel`
- `enterCode`
- `enterCodeAria`
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

### ✅ Example with Static Texts

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

### 🌐 Example with TYPO3 Localization

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
