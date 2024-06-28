<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AltchaService
{
    protected array $configuration = [];
    public function __construct(protected ExtensionConfiguration $extensionConfiguration)
    {
        $this->init();
    }

    protected function init()
    {
        $this->configuration = $this->extensionConfiguration->get('altcha');
    }

    public function createChallenge(string $salt = null, $number = null): array
    {
        $hmac = $this->configuration['hmac'] ?? 'S3cr3t';
        $salt = $salt ?? bin2hex(random_bytes(12));
        $number = $number ?? random_int(5000, 15000);
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
