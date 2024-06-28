<?php

declare(strict_types=1);

/**
 * This file is part of the "altcha" Extension for TYPO3 CMS.
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BBysaeth\Typo3Altcha\Validation;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class AltchaValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;
    public function __construct(protected AltchaService $altchaService)
    {
    }
    protected function isValid($value): void
    {
        if(!$value || empty($value)) {
            $this->addError('This field is mandatory.', 1719693214);
        }
        elseif ($this->altchaService->validate($value) === false) {
            $this->addError('ALtcha not correct validatet.', 1719694187);
        }
    }
}