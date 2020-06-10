<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $_EXTKEY,
    'Pi1',
    'LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.title'
);

$pluginSignature = strtolower($extensionName) . '_pi1';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature,
    'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript',
    'Municipal statutes');
