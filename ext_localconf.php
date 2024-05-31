<?php
call_user_func(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:form/Resources/Private/Language/Database.xlf'][] =
        'EXT:ns_friendlycaptcha/Resources/Private/Language/Backend.xlf';

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        't3-form-icon-recaptcha',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:ns_friendlycaptcha/Resources/Public/Images/reCaptcha_sw.svg']
    );

    // @extensionScannerIgnoreLine
    if(version_compare(TYPO3_version, '10.0.0', '<=')){
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
            module.tx_form.settings.yamlConfigurations {
                1974 = EXT:ns_friendlycaptcha/Configuration/Yamlv9/BaseSetup.yaml
                1975 = EXT:ns_friendlycaptcha/Configuration/Yamlv9/FormEditorSetup.yaml
            }
        ');
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
            module.tx_form {
                settings {
                    yamlConfigurations {
                        1974 = EXT:ns_friendlycaptcha/Configuration/Yaml/FormSetup.yaml
                    }
                }
            }
        ');
}
    
});
