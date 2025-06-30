# TYPO3 Extension ``Altcha``

This TYPO3 extension integrates Altcha, an innovative alternative to traditional captchas, into the Form Extension. Altcha employs a proof-of-work approach to safeguard forms against spam and abuse without requiring users to solve visual puzzles.

Key Features:

* Seamless integration with the TYPO3 Form Extension
* Configurable difficulty levels for the proof-of-work mechanism
* Automatic validation of Altcha responses
* Enhanced protection against automated bots
* User-friendly alternative to conventional captchas

The extension empowers developers to easily incorporate Altcha into existing forms, thereby enhancing security without compromising user experience.

## Features

* Altcha spam protection field for ext:form
* Customizable expiration time of challenges
* Scheduler task for removing obsolete (expired and solved) challenges

## Installation

### via Composer

The recommended way to install this TYPO3 extension is by using [Composer](https://getcomposer.org):

```bash
composer require bbysaeth/typo3-altcha
```

Add the static template and update the database schema via the install tool.

This TYPO3 extension is licensed under the GNU General Public License Version 2 (GPLv2).

### via TYPO3 Extension Repository

Download and install the extension with the Extension Manager module or directly from the
[TER](https://extensions.typo3.org/extension/altcha/).

## Configuration

### Extension Configuration

`HMac Sercret Key (basic.hmac [string])`  
HMAC secret key for challenge generation. If not defined, TYPO3's encryption key will be used.

### TypoScript Configuration Settings

The following TypoScript settings are available:

* `plugin.tx_altcha.minimumComplexity` *(integer)* – Minimum number for range of complexity
* `plugin.tx_altcha.maximumComplexity` *(integer)* – Maximum number for range of complexity (must be larger than minimumComplexity)
* `plugin.tx_altcha.expires` *(integer)* – Seconds after which the challenge expires
* `plugin.tx_altcha.hideFooter` *(bool)* – Hide/Show Altcha footer link in field
* `plugin.tx_altcha.hideAltchaLogo` *(bool)* – Hide/Show Altcha logo in field
* `plugin.tx_altcha.auto` *(Choose: disabled, onload, onfocus)* – Enable/Disable auto verify onload or onfocus

---

## 🧩 Customizing Altcha Widget Texts

You can customize the texts displayed by the Altcha widget (e.g., for translation) by overriding the `AltchaTranslations.html` partial.

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
              20: 'EXT:my_extension/Resources/Private/Frontend/Partials/'
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

* `ariaLinkLabel`
* `enterCode`
* `enterCodeAria`
* `error`
* `expired`
* `footer`
* `getAudioChallenge`
* `label`
* `loading`
* `reload`
* `verify`
* `verificationRequired`
* `verified`
* `verifying`
* `waitAlert`

---

### ✅ Example with Static Texts

**`EXT:my_extension/Resources/Private/Frontend/Partials/AltchaTranslations.html`:**

```html
<f:spaceless>
    <f:format.json value="{
        label: 'I am not a robot',
        verified: 'Verified',
        verifying: 'Verifying'
    }" />
</f:spaceless>
```

---

### 🌐 Example with TYPO3 Localization

If you want to use TYPO3’s localization, add the relevant labels to your `locallang.xlf`.

**Partial Example:**

```html
<f:spaceless>
    <f:format.json value="{
        ariaLinkLabel: f:translate(key: 'altcha.ariaLinkLabel', extensionName: 'my_extension'),
        error: f:translate(key: 'altcha.error', extensionName: 'my_extension'),
        verified: f:translate(key: 'altcha.verified', extensionName: 'my_extension')
    }" />
</f:spaceless>
```

---

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.