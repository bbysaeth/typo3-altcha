<?php

declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Services;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
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
    {
        $this->configuration = $this->extensionConfiguration->get('altcha');
        $this->typoscriptSetup = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        $hmacKey = trim($this->configuration['hmac'] ?? '');
        if ($hmacKey === '') {
            $hmacKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        }
        $this->altchaClient = new Altcha($hmacKey);
    }

    protected function getTypoScriptSettings(): array
    {
        $typoScriptSetup = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $settings = $typoScriptSetup['plugin.']['tx_altcha.']['settings.'] ?? [];
        return $settings;
    }

    public function createChallenge(): array
    {
        $typoscriptSettings = $this->getTypoScriptSettings();
        
        $maxComplexity = $typoscriptSettings['maximumComplexity'] ?? 15000; 
        $expiresInSeconds = (int)($typoscriptSettings['expires'] ?? 360); 

        $options = new ChallengeOptions(
            maxNumber: (int)$maxComplexity,
            expires: (new \DateTimeImmutable())->add(new \DateInterval('PT' . $expiresInSeconds . 'S'))
            
        );

        $altchaChallenge = $this->altchaClient->createChallenge($options);
        
        $domainChallenge = new Challenge();
        $domainChallenge->setChallenge($altchaChallenge->challenge); 
        $this->challengeRepository->add($domainChallenge);

        return [
            'algorithm' => $altchaChallenge->algorithm,
            'challenge' => $altchaChallenge->challenge,
            'salt' => $altchaChallenge->salt,
            'signature' => $altchaChallenge->signature,
        ];
    }

    public function validate(string $payload): bool
    {
        $payloadArray = json_decode(base64_decode($payload, true), true);

        if ($payloadArray === null) {
            return false;
        }

        $isAltchaSolutionValid = $this->altchaClient->verifySolution($payloadArray);

        if (!$isAltchaSolutionValid) {
            return false;
        }
        
        if (!isset($payloadArray['challenge']) || !is_string($payloadArray['challenge'])) {
            return false;
        }
        $challengeString = $payloadArray['challenge'];

        $domainChallenge = $this->challengeRepository->findOneBy(['challenge' => $challengeString, 'isSolved' => false]);

        if (!$domainChallenge instanceof Challenge) {
            return false;
        }

        $domainChallenge->setIsSolved(true);
        $this->challengeRepository->update($domainChallenge);
        $this->persistenceManager->persistAll();

        return true;
    }

    public function removeObsoleteChallenges(bool $dryRun, bool $removedSolvedChallenges) : int {
        $challenges = $this->challengeRepository->findAll();
        $count = 0;
        $typoscriptSettings = $this->getTypoScriptSettings(); 
        $expiresSetting = (int)($typoscriptSettings['expires'] ?? 360);

        foreach($challenges as $challenge) {
            $expires = MathUtility::forceIntegerInRange($this->getTypoScriptSettings()['expires'], 30, 2000000000, 360);
            if($challenge->getTstamp() + $expiresSetting < time() || ($removedSolvedChallenges && $challenge->getIsSolved())) {
                if(!$dryRun) {
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
