<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
});