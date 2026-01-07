<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') || die('Access denied.');

$versionNumber =  VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());

if ($versionNumber <= 13) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/Database.xlf'][] = 'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';
}else{
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['EXT:form/Resources/Private/Language/Database.xlf'][] = 'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';
    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
            settings {
                yamlConfigurations {
                    1974 = EXT:ns_friendlycaptcha/Configuration/Yaml/FormSetup.yaml
                }
            }
        }
    ');
}