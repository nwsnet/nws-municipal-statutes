<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'Pi1',
            'LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.title'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript',
            'Municipal statutes'
        );

        //Add flexform of the map for pi1
        $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey);
        $pluginSignature = strtolower($extensionName) . '_pi1';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            'FILE:EXT:' . $extKey . '/Configuration/FlexForms/flexform.xml'
        );
    },
    'nws_municipal_statutes'
);