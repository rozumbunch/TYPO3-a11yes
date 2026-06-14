<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'A11yes',
    'description' => 'A11yes – Accessibility extension',
    'category' => 'fe',
    'author' => 'Rozumbunch',
    'author_company' => 'Rozumbunch',
    'author_email' => 'contact@rozumbunch.de',
    'state' => 'stable',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.0.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['Rozumbunch\\A11yes\\' => 'Classes'],
    ],
    'icon' => 'Resources/Public/Icons/Extension.svg',
];
