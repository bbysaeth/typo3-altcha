<?php
declare(strict_types=1);

if (!defined('TYPO3')) {
    die('Access denied.');
}

$ll = 'LLL:EXT:altcha/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_typo3altcha_domain_model_challenge',
        'label' => 'challenge',
        'label_alt' => 'uid',
        'label_alt_force' => 0,
        // Challenges are managed by the extension only. Editing them in the backend
        // could reset is_solved and thus re-enable an already consumed challenge.
        'readOnly' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'altcha-icon'
        ],
        'searchFields' => 'uid,challenge',
    ],
    'types' => [
        '0' => [
            'showitem' => 'challenge, is_solved, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden',
        ],
    ],
    'columns' => [
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'challenge' => [
            'label' => $ll . 'tx_typo3altcha_domain_model_challenge.challenge',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'is_solved' => [
            'label' => $ll . 'tx_typo3altcha_domain_model_challenge.is_solved',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ]
    ]
];
