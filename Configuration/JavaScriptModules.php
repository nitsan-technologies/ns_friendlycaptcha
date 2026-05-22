<?php

return [
    'dependencies' => [
        'backend',
        'core',
        'form',
    ],
    'imports' => [
        '@nitsan/ns-friendlycaptcha/' => 'EXT:ns_friendlycaptcha/Resources/Public/JavaScript/',
        '@nitsan/ns-friendlycaptcha/frontend/' => 'EXT:ns_friendlycaptcha/Resources/Public/JavaScript/Frontend/',
    ],
];
