<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
    'a11yes-toolbar',
    SvgIconProvider::class,
    ['source' => 'EXT:a11yes/Resources/Public/Icons/a11yes.svg']
);
