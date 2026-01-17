<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Controller;

use BBysaeth\Typo3Altcha\Services\AltchaService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class AltchaProxyController
{
    /**
     * Generate a local challenge (uncached endpoint).
     * This solves the form caching issue where challengejson gets cached.
     *
     * @param string $content
     * @param array $conf
     * @return string JSON response
     */
    public function localChallenge(string $content = '', array $conf = []): string
    {
        $altchaService = GeneralUtility::makeInstance(AltchaService::class);
        $challenge = $altchaService->createChallenge();
        return json_encode($challenge, JSON_THROW_ON_ERROR);
    }

    /**
     * Proxy the challenge request to the configured self-hosted Altcha server.
     * Signature matches TypoScript USER_INT `userFunc`.
     *
     * @param string $content
     * @param array $conf
     * @return string JSON response
     */
    public function challenge(string $content = '', array $conf = []): string
    {
        $settings = $this->getSettings();
        $challengeUrl = trim($settings['challengeUrl'] ?? '');
        if ($challengeUrl === '') {
            return json_encode(['error' => 'Altcha challengeUrl not configured'], JSON_THROW_ON_ERROR);
        }

        $apiKey = trim($settings['apiKey'] ?? '');
        $headers = [
            'accept' => 'application/json',
        ];
        if ($apiKey !== '') {
            // Default to Authorization header; many servers expect Bearer tokens
            $headers['authorization'] = 'Bearer ' . $apiKey;
            // Also include a custom header for flexibility
            $headers['x-altcha-api-key'] = $apiKey;
        }

        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request($challengeUrl, 'GET', [
            'headers' => $headers,
            'allow_redirects' => false,
        ]);

        $body = (string) $response->getBody();
        return $body !== '' ? $body : json_encode(['error' => 'Empty response from Altcha server'], JSON_THROW_ON_ERROR);
    }

    /**
     * Proxy the verify request to the configured self-hosted Altcha server.
     * Signature matches TypoScript USER_INT `userFunc`.
     *
     * @param string $content
     * @param array $conf
     * @return string JSON response
     */
    public function verify(string $content = '', array $conf = []): string
    {
        $settings = $this->getSettings();
        $verifyUrl = trim($settings['verifyUrl'] ?? '');
        if ($verifyUrl === '') {
            return json_encode(['error' => 'Altcha verifyUrl not configured'], JSON_THROW_ON_ERROR);
        }

        $apiKey = trim($settings['apiKey'] ?? '');
        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];
        if ($apiKey !== '') {
            $headers['authorization'] = 'Bearer ' . $apiKey;
            $headers['x-altcha-api-key'] = $apiKey;
        }

        $payload = file_get_contents('php://input') ?: '';
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request($verifyUrl, 'POST', [
            'headers' => $headers,
            'body' => $payload,
            'allow_redirects' => false,
        ]);

        $body = (string) $response->getBody();
        return $body !== '' ? $body : json_encode(['error' => 'Empty response from Altcha server'], JSON_THROW_ON_ERROR);
    }

    /**
     * Retrieve plugin settings from TypoScript.
     *
     * @return array<string, mixed>
     */
    protected function getSettings(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $fullTs = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        return $fullTs['plugin.']['tx_altcha.']['settings.'] ?? [];
    }
}
