<?php
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/Database.xlf'][] =
        'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        't3-form-icon-recaptcha',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:ns_friendlycaptcha/Resources/Public/Images/reCaptcha_sw.svg']
    );


    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy'] = false;

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form {
    settings {
        yamlConfigurations {
            1974 = EXT:ns_friendlycaptcha/Configuration/Yaml/FormSetup.yaml
        }
    }
}
    ');
    
