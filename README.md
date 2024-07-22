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
* Scheduler task for removing obsolete(expired and solved) challenges

## Installation

### via Composer

The recommended way to install TYPO3 Console is by using [Composer](https://getcomposer.org):

    composer require bbysaeth/altcha

Add static template and update database scheme.

This TYPO3 extension is licensed under the GNU General Public License Version 2 (GPLv2).

### via TYPO3 Extension Repository

Download and install the extension with the extension manager module or directly from the
[TER](https://extensions.typo3.org/extension/altcha/).

## Configuration
### Extension Configuration

`HMac Sercret Key (basic.hmac [string])` HMAC secret key for challenge generation, if not defined TYPO3 encryption key will be used.

### TypoScript configuration settings
The following TypoScript settings are available.

* `plugin.tx_altcha.minimumComplexity`*(integer)* Minimum number for range of complexity
* `plugin.tx_altcha.maximumComplexity` *(integer)* Maximum number for range of complexity, must be bigger than minimumComplexity
* `plugin.tx_altcha.expires` *(integer)* Seconds after the challenge expires
* `plugin.tx_altcha.hideFooter` *(bool)* Hide/Show altcha footer link in field
* `plugin.tx_altcha.hideAltchaLogo` *(bool)* Hide/Show altcha logo in field


## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
