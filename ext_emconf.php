<?php

$EM_CONF['ns_friendlycaptcha'] = [
    'title' => '[NITSAN]Friendly Captcha',
    'description' => 'Integrates Friendly captcha in EXT:form
        and via TypoScript renderer Easy on Humans, Hard on Bots',
    'category' => 'fe',
    'author' => 'NITSAN Technologies Pvt Ltd',
    'author_email' => 'sanjay@nitsan.in',
    'author_company' => 'NITSAN Technologies Pvt Ltd',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
