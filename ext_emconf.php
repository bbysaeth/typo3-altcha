<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'ALTCHA spam protection for ext:form',
    'description' => 'TYPO3 form element for spam protection with ALTCHA Widget v3.',
    'category' => 'fe',
    'author' => 'Benjamin Bysäth',
    'author_email' => 'benjamin@bysaeth.de',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'backend' => '13.4.0-14.99.99',
            'typo3' => '13.4.0-14.99.99',
            'form' => '13.4.0-14.99.99',
            'extbase' => '13.4.0-14.99.99',
            'fluid' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => '13.4.0-14.99.99',
        ],
    ]
];
