<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AltchaService
{
    protected array $configuration = [];
    protected array $typoscriptSetup = [];
    public function __construct(protected ExtensionConfiguration $extensionConfiguration, protected ConfigurationManagerInterface $configurationManager)
    {
        $this->init();
    }

    protected function init()
    {
        $this->configuration = $this->extensionConfiguration->get('altcha');
        $this->typoscriptSetup = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }

    protected function getTypoScriptSettings(): array
    {
        $typoScriptSetup = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $settings = $typoScriptSetup['plugin.']['tx_altcha.']['settings.'] ?? [];
        return $settings;
    }

    public function createChallenge(string $salt = null, $number = null): array
    {
        $hmac = $this->configuration['hmac'] ?? 'S3cr3t';
        $typoscriptSettings = $this->getTypoScriptSettings();

        $minComplexity = MathUtility::forceIntegerInRange($typoscriptSettings['minimumComplexity'], 0, 2000000000, 15000);
        $maxComplexity = MathUtility::forceIntegerInRange($typoscriptSettings['maximumComplexity'], 0, 2000000000, 15000);
        $salt = $salt ?? bin2hex(random_bytes(12));
        $number = $number ?? random_int($minComplexity, $maxComplexity);
        $challenge = hash('sha256', $salt . $number);
        $signature = hash_hmac('sha256', $challenge, $hmac);

        return [
            'algorithm' => 'SHA-256',
            'challenge' => $challenge,
            'salt' => $salt,
            'signature' => $signature,
        ];
    }

    public function validate(string $payload): bool
    {
        $json = json_decode(base64_decode($payload, true));

        if ($json === null) {
            return false;
        }

        $expectedResult = $this->createChallenge($json->salt, $json->number);
        $result = $json->signature === $expectedResult['signature']
            && $json->challenge === $expectedResult['challenge']
            && $json->algorithm === $expectedResult['algorithm'];
        return $result;
    }
}
