<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class AltchaViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * Settings that are passed to the widget as booleans and therefore must not
     * stay TypoScript strings, see normalizeBooleanSettings().
     */
    protected const BOOLEAN_SETTINGS = [
        'hideAltchaLogo',
        'hideFooter',
    ];

    /**
     * Same mapping the TYPO3 site settings use for their bool type.
     */
    protected const BOOLEAN_STRING_MAP = [
        '' => false,
        '0' => false,
        '1' => true,
        'false' => false,
        'true' => true,
        'off' => false,
        'on' => true,
        'no' => false,
        'yes' => true,
    ];

    protected $typoscriptSettings = [];

    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $typoScriptSetup = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $this->typoscriptSettings = $this->normalizeBooleanSettings(
            $typoScriptSetup['plugin.']['tx_altcha.']['settings.'] ?? []
        );
        parent::__construct();
    }

    /**
     * TypoScript delivers every value as a string, so a disabled boolean setting
     * arrives as "0" when it comes from the static template constants. Once JSON
     * encoded into the widget's configuration attribute, "0" is a non-empty string
     * and therefore truthy in JavaScript, which would hide the logo and the footer
     * even though the setting is switched off. Cast them to real booleans instead.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    protected function normalizeBooleanSettings(array $settings): array
    {
        foreach (self::BOOLEAN_SETTINGS as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $value = $settings[$key];

            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                $settings[$key] = self::BOOLEAN_STRING_MAP[$normalized] ?? (bool)$normalized;
                continue;
            }

            $settings[$key] = (bool)$value;
        }

        return $settings;
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
        $container = $this->templateVariableContainer;
        $container->add('name', $name);
        $container->add('settings', $this->typoscriptSettings);
        $content = $this->renderChildren();
        
        return $content;
    }
}
