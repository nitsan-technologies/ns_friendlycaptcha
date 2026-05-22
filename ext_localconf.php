<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

call_user_func(static function (): void {
    ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form.settings.yamlConfigurations.1974 = EXT:ns_friendlycaptcha/Configuration/Yaml/FormSetup.yaml
    ');

    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['EXT:form/Resources/Private/Language/Database.xlf'][] =
        'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';
});
