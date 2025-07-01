<?php

$EM_CONF['ns_friendlycaptcha'] = [
    'title' => 'TYPO3 Friendly Captcha',
    'description' => 'Protect your TYPO3 forms from bots without annoying puzzles. Friendly Captcha runs seamlessly in the background and complies fully with GDPR regulations.', 
    
    'category' => 'plugin',
    'author' => 'Team T3Planet',
    'author_email' => 'info@t3planet.de',
    'author_company' => 'T3Planet',
    'state' => 'stable',
    'version' => '13.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
