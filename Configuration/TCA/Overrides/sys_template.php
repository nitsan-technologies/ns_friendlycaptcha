<?php

defined('TYPO3') or die();

// Add Default TS to Include static (from extensions)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ns_friendlycaptcha',
    'Configuration/TypoScript/',
    'NS FriendlyCaptcha'
);
