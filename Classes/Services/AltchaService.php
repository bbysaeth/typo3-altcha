<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

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
        $typoscriptSettings = $this->getTypoScriptSettings();

        if (!$number) {
            $minComplexity = $typoscriptSettings['minimumComplexity'];
            $maxComplexity = $typoscriptSettings['maximumComplexity'];

            if ($maxComplexity < $minComplexity) {
                throw new InvalidArgumentValueException('Maximum complexity must be greater or equal to minimum complexity');
            }

            $minInt = MathUtility::forceIntegerInRange($minComplexity, 0, 2000000000, 15000);
            $maxInt = MathUtility::forceIntegerInRange($maxComplexity, 0, 2000000000, 15000);
            $number = random_int($minInt, $maxInt);
        }
        $salt = $salt ?? bin2hex(random_bytes(12));
        $hashedChallenge = hash('sha256', $salt . $number);
        $challenge = new Challenge();
        $challenge->setChallenge($hashedChallenge);
        $this->challengeRepository->add($challenge);

        return [
            'algorithm' => 'SHA-256',
            'challenge' => $hashedChallenge,
            'salt' => $salt,
            'signature' => $this->getSignature($challenge->getChallenge()),
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

    private function getSignature(string $string): string
    {
        $hmac = trim($this->configuration['hmac']) !== '' ? trim($this->configuration['hmac']) : $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        return hash_hmac('sha256', $string, $hmac);
    }
}
