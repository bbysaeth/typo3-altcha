<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

use BBysaeth\Typo3Altcha\Domain\Model\Challenge;
use BBysaeth\Typo3Altcha\Domain\Repository\ChallengeRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class AltchaService
{
    protected array $configuration = [];
    protected array $typoscriptSetup = [];
    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected ConfigurationManagerInterface $configurationManager,
        protected ChallengeRepository $challengeRepository,
        protected PersistenceManagerInterface $persistenceManager
    ) {
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
        $challenge = new Challenge();
        $minComplexity = MathUtility::forceIntegerInRange($typoscriptSettings['minimumComplexity'], 0, 2000000000, 15000);
        $maxComplexity = MathUtility::forceIntegerInRange($typoscriptSettings['maximumComplexity'], 0, 2000000000, 15000);
        $salt = $salt ?? bin2hex(random_bytes(12));
        $number = $number ?? random_int($minComplexity, $maxComplexity);
        $hashedChallenge = hash('sha256', $salt . $number);
        $challenge->setChallenge($hashedChallenge);
        $signature = hash_hmac('sha256', $challenge->getChallenge(), $hmac);
        $this->challengeRepository->add($challenge);

        return [
            'algorithm' => 'SHA-256',
            'challenge' => $hashedChallenge,
            'salt' => $salt,
            'signature' => $signature,
        ];
    }

    public function validate(string $payload): bool
    {
        $json = json_decode(base64_decode($payload, true));
        $typoscriptSettings = $this->getTypoScriptSettings();

        if ($json === null) {
            return false;
        }
        $challenge = $this->challengeRepository->findOneBy(['challenge' => $json->challenge, 'isSolved' => false]);
        if(!$challenge || $challenge instanceof Challenge === false) {
            return false;
        }
        $expires = MathUtility::forceIntegerInRange($typoscriptSettings['expires'], 30, 2000000000, 360);
        if($challenge->getTstamp() + $expires < time()) {
            $challenge->setDeleted(true);
            $this->challengeRepository->remove($challenge);
            $this->persistenceManager->persistAll();
            return false;
        }

        $challenge->setIsSolved(true);
        $this->challengeRepository->update($challenge);
        $this->persistenceManager->persistAll();

        $expectedResult = $this->createChallenge($json->salt, $json->number);
        $result = $json->signature === $expectedResult['signature']
            && $json->challenge === $expectedResult['challenge']
            && $json->algorithm === $expectedResult['algorithm'];
        return $result;
    }

    public function removeObsoleteChallenges(bool $dryRun, bool $removedSolvedChallenges) : int {
        $challenges = $this->challengeRepository->findAll();
        $count = 0;
        foreach($challenges as $challenge) {
            $expires = MathUtility::forceIntegerInRange($this->getTypoScriptSettings()['expires'], 30, 2000000000, 360);
            if($challenge->getTstamp() + $expires < time() || ($removedSolvedChallenges && $challenge->getIsSolved())) {
                if(!$dryRun) {
                    $this->challengeRepository->remove($challenge);
                }
                $count++;
            }
        }
        $this->persistenceManager->persistAll();
        return $count;
    }
}
