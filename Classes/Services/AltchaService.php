<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Challenge as AltchaChallenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\ServerSignature;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\VerifySolutionOptions;
use BBysaeth\Typo3Altcha\Domain\Model\Challenge;
use BBysaeth\Typo3Altcha\Domain\Repository\ChallengeRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class AltchaService
{
    protected array $configuration = [];
    protected array $typoscriptSetup = [];
    protected Altcha $altchaClient;

    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected ConfigurationManagerInterface $configurationManager,
        protected ChallengeRepository $challengeRepository,
        protected PersistenceManagerInterface $persistenceManager
    ) {
        $this->init();
    }

    protected function init()
    : void
    {
        $this->configuration = $this->extensionConfiguration->get('altcha');
        
        // In CLI context, ConfigurationManager may not have a request
        // and cannot load TypoScript. We catch this and use defaults instead.
        try {
            $this->typoscriptSetup = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
            );
        } catch (\Exception $e) {
            // CLI context use empty array, defaults will be used
            $this->typoscriptSetup = [];
        }

        $hmacKey = trim($this->configuration['hmac'] ?? '');
        if ($hmacKey === '') {
            $hmacKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        }
        $this->altchaClient = new Altcha(hmacSignatureSecret: $hmacKey);
    }

    protected function getTypoScriptSettings(): array
    {
        return $this->typoscriptSetup['plugin.']['tx_altcha.']['settings.'] ?? [];
    }

    public function createChallenge(): array
    {
        $typoscriptSettings = $this->getTypoScriptSettings();
        $maxComplexity = (int) ($typoscriptSettings['maximumComplexity'] ?? 15000);
        $expiresInSeconds = (int) ($typoscriptSettings['expires'] ?? 360);
        $algorithm = new Pbkdf2();

        $altchaChallenge = $this->altchaClient->createChallenge(new CreateChallengeOptions(
            algorithm: $algorithm,
            cost: max(1, $maxComplexity),
            expiresAt: (new \DateTimeImmutable())->add(new \DateInterval('PT' . $expiresInSeconds . 'S')),
        ));
        
        $domainChallenge = new Challenge();
        $domainChallenge->setChallenge($altchaChallenge->parameters->nonce);
        $this->challengeRepository->add($domainChallenge);
        // Ensure the challenge is persisted immediately, because validation occurs in a separate request.
        $this->persistenceManager->persistAll();

        return $altchaChallenge->toArray();
    }

    public function validate(string $payload): bool
    {
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return false;
        }

        $payloadArray = json_decode($decoded, true);

        if (!is_array($payloadArray)) {
            return false;
        }

        $payloadObject = $this->createPayloadFromArray($payloadArray);

        if ($payloadObject instanceof Payload) {
            try {
                $verificationResult = $this->altchaClient->verifySolution(new VerifySolutionOptions(
                    payload: $payloadObject,
                    algorithm: $this->createAlgorithmFromName($payloadObject->challenge->parameters->algorithm),
                ));
            } catch (InvalidArgumentValueException) {
                return false;
            }

            if ($verificationResult->verified) {
                $challengeNonce = $payloadObject->challenge->parameters->nonce;
                $domainChallenge = $this->challengeRepository->findOneBy(['challenge' => $challengeNonce, 'isSolved' => false]);

                if (!$domainChallenge instanceof Challenge) {
                    return false;
                }

                $domainChallenge->setIsSolved(true);
                $this->challengeRepository->update($domainChallenge);
                $this->persistenceManager->persistAll();

                return true;
            }
        }

        // If not a direct solution, attempt to verify a server signature payload
        $serverVerification = ServerSignature::verifyServerSignature(
            data: $payloadArray,
            hmacKey: $this->getHmacKey(),
        );

        if ($serverVerification->verified) {
            // For server verification payloads, the server already ensures validity and expiry.
            // No local challenge persistence is required.
            return true;
        }

        return false;
    }

    protected function getHmacKey(): string
    {
        $hmacKey = trim($this->configuration['hmac'] ?? '');

        if ($hmacKey === '') {
            return (string) $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        }

        return $hmacKey;
    }

    protected function createAlgorithmFromName(string $algorithmName): Pbkdf2
    {
        // Step 1 only supports PBKDF2/SHA-256 for locally generated challenges.
        if ($algorithmName !== 'PBKDF2/SHA-256') {
            throw new InvalidArgumentValueException('Unsupported ALTCHA algorithm: ' . $algorithmName, 1769772101);
        }

        return new Pbkdf2();
    }

    /**
     * @param array<string, mixed> $payloadArray
     */
    protected function createPayloadFromArray(array $payloadArray): ?Payload
    {
        if (!isset($payloadArray['challenge'], $payloadArray['solution'])
            || !is_array($payloadArray['challenge'])
            || !is_array($payloadArray['solution'])
        ) {
            return null;
        }

        $challengeArray = $payloadArray['challenge'];
        $solutionArray = $payloadArray['solution'];

        if (!isset($challengeArray['parameters']) || !is_array($challengeArray['parameters'])) {
            return null;
        }

        if (!isset($solutionArray['counter'], $solutionArray['derivedKey'])
            || !is_string($solutionArray['derivedKey'])
        ) {
            return null;
        }

        $counter = filter_var($solutionArray['counter'], FILTER_VALIDATE_INT);

        if ($counter === false) {
            return null;
        }

        $time = null;
        if (isset($solutionArray['time'])) {
            $timeValue = filter_var($solutionArray['time'], FILTER_VALIDATE_FLOAT);
            if ($timeValue === false) {
                return null;
            }
            $time = (float) $timeValue;
        }

        $challenge = new AltchaChallenge(
            parameters: ChallengeParameters::fromArray($challengeArray['parameters']),
            signature: isset($challengeArray['signature']) && is_string($challengeArray['signature'])
                ? $challengeArray['signature']
                : null,
        );

        return new Payload(
            challenge: $challenge,
            solution: new Solution(
                counter: (int) $counter,
                derivedKey: $solutionArray['derivedKey'],
                time: $time,
            ),
        );
    }

    public function removeObsoleteChallenges(bool $dryRun, bool $removedSolvedChallenges): int
    {
        $challenges = $this->challengeRepository->findAll();
        $count = 0;
        $typoscriptSettings = $this->getTypoScriptSettings();
        $expiresSetting = (int) ($typoscriptSettings['expires'] ?? 360);
        $expires = MathUtility::forceIntegerInRange($expiresSetting, 30, 2000000000, 360);

        foreach ($challenges as $challenge) {
            if ($challenge->getTstamp() + $expires < time() || ($removedSolvedChallenges && $challenge->getIsSolved())) {
                if (!$dryRun) {
                    $this->challengeRepository->remove($challenge);
                }
                $count++;
            }
        }
        if ($count > 0 && !$dryRun) {
            $this->persistenceManager->persistAll();
        }

        return $count;
    }
}
