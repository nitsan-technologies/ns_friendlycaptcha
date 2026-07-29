<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

defined('TYPO3') || die ('Access denied.');

// BC: keep r:form.recaptcha working without a separate RecaptchaViewHelper.php file
class_alias(
    \NITSAN\NsFriendlycaptcha\ViewHelpers\Form\FriendlyCaptchaViewHelper::class,
    \NITSAN\NsFriendlycaptcha\ViewHelpers\Form\RecaptchaViewHelper::class
);

$versionNumber =  VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());

if ($versionNumber['version_main'] <= 13) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/Database.xlf'][] = 'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';
}else{
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['EXT:form/Resources/Private/Language/Database.xlf'][] = 'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';
    // TYPO3 14+: register frontend + backend form YAML globally so validators
    // are available even when site-set TypoScript does not merge reliably.
    ExtensionManagementUtility::addTypoScriptSetup('
        plugin.tx_form {
            settings {
                yamlConfigurations {
                    1975 = EXT:ns_friendlycaptcha/Configuration/Yaml/BaseSetup.yaml
                }
            }
            view {
                partialRootPaths.1975 = EXT:ns_friendlycaptcha/Resources/Private/Frontend/Partials/
            }
        }
        module.tx_form {
            settings {
                yamlConfigurations {
                    1974 = EXT:ns_friendlycaptcha/Configuration/Yaml/FormSetupV14.yaml
                }
            }
        }
        page.includeJSFooterlibs {
            widgetmodule = EXT:ns_friendlycaptcha/Resources/Public/JavaScript/widget.module.min.js
            widgetmodule.type = module
            widgetmodule.async = 1
            widgetmodule.defer = 1
            widgetmin = EXT:ns_friendlycaptcha/Resources/Public/JavaScript/widget.min.js
            widgetmin.nomodule = 1
            widgetmin.async = 1
            widgetmin.defer = 1
        }
    ');
}