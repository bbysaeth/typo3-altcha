<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Altcha spam protection for ext:form',
    'description' => 'TYPO3 form element for spam protection by utilizing the proof-of-work mechanism Altcha.',
    'category' => 'frontend',
    'author' => 'Benjamin Bysäth',
    'author_email' => 'benjamin@bysaeth.de',
    'state' => 'stable',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'form' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];