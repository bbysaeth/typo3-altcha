<?php

use BBysaeth\Typo3Altcha\Domain\Repository\ChallengeRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') or die();

call_user_func(function () {
    ExtensionManagementUtility::addTypoScriptSetup(
        trim('
        plugin.tx_form.settings.yamlConfigurations {
            175 = EXT:altcha/Configuration/Yaml/FormSetup.yaml
        }
        module.tx_form.settings.yamlConfigurations {
            175 = EXT:altcha/Configuration/Yaml/FormSetup.yaml
        }'
        )
    );

    // Offer the challenge table to the core's table garbage collection, so that
    // cleaning up does not require the extension's own console command. The task
    // deletes with a single statement and only supports whole days, which is coarse
    // but safe: a challenge is worthless once its signed expiry has passed, the row
    // only has to outlive it. `::class` resolves to a string without autoloading,
    // so this stays harmless when EXT:scheduler is not installed.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']
        [TableGarbageCollectionTask::class]['options']['tables'][ChallengeRepository::TABLE_NAME] = [
            'dateField' => 'tstamp',
            'expirePeriod' => 1,
        ];
});