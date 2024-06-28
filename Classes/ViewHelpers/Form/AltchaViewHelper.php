<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\ViewHelpers\Form;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use BBysaeth\Typo3Altcha\Validation\AltchaValidator;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class AltchaViewHelper extends AbstractFormFieldViewHelper
{
    public function __construct(protected AltchaService $altchaService)
    {
        parent::__construct();
    }

    public function render(): string
    {   
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $challenge = $this->altchaService->createChallenge();
        $container = $this->templateVariableContainer;
        $container->add('name', $name);
        $container->add('challenge', $challenge);
        $content = $this->renderChildren();
        
        return $content;
    }

}