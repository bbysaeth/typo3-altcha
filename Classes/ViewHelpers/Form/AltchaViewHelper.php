<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\ViewHelpers\Form;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class AltchaViewHelper extends AbstractFormFieldViewHelper
{
    protected $typoscriptSettings = [];

    public function __construct(protected AltchaService $altchaService, ConfigurationManagerInterface $configurationManager)
    {
        $typoScriptSetup = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $this->typoscriptSettings = $typoScriptSetup['plugin.']['tx_altcha.']['settings.'] ?? [];
        parent::__construct();
    }
    
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('id', 'string', 'Identifier of the field', true);
        $this->registerArgument('class', 'string', 'HTML Class for the field', true);
    }

    public function render(): string
    {   
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $challenge = $this->altchaService->createChallenge();
        $container = $this->templateVariableContainer;
        $container->add('name', $name);
        $container->add('challenge', $challenge);
        $container->add('settings', $this->typoscriptSettings);
        $content = $this->renderChildren();
        
        return $content;
    }

}