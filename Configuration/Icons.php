<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    't3-form-icon-friendlycaptcha' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_friendlycaptcha/Resources/Public/Icons/friendlycaptcha.svg',
    ],
    // BC alias for existing form editor references
    't3-form-icon-recaptcha' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_friendlycaptcha/Resources/Public/Icons/friendlycaptcha.svg',
    ],
];
