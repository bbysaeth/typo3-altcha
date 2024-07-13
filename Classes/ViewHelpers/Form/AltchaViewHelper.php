<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\ViewHelpers\Form;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use BBysaeth\Typo3Altcha\Validation\AltchaValidator;
use Doctrine\DBAL\Configuration;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
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
    
    public function render(): string
    {   
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $challenge = $this->altchaService->createChallenge();
        $container = $this->templateVariableContainer;
        $container->add('name', $name);
        $container->add('challenge', $challenge);
        $container->add('customization', $this->typoscriptSettings);
        $content = $this->renderChildren();
        
        return $content;
    }

}