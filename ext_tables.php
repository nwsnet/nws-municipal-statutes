<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase('nws_municipal_statutes');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'nws_municipal_statutes',
    'Pi1',
    'LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.title'
);

$pluginSignature = strtolower($extensionName) . '_pi1';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature,
    'FILE:EXT:' . 'nws_municipal_statutes' . '/Configuration/FlexForms/flexform.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('nws_municipal_statutes', 'Configuration/TypoScript',
    'Municipal statutes');
