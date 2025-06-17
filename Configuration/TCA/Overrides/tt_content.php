<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2025 Dirk Meinke <typo3@die-netzwerkstatt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

$extKey = 'nws_municipal_statutes';
$cType = 'list';

if (defined('TYPO3_version')) {
    $versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
} else {
    $version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version();
    $versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($version);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $extKey,
    'Configuration/TypoScript',
    'Municipal statutes'
);
$pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $extKey,
    'Pi1',
    'LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.title',
    'EXT:nws_municipal_statutes/Resources/Public/Icons/ce_wiz.gif',
    'plugins',
    'LLL:EXT:nws_municipal_statutes/Resources/Private/Language/locallang.xlf:tx_nwsmunicipalstatutes_pi1.wiz_description'
);

if (null === $pluginSignature) {
    $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey);
    $pluginSignature = strtolower($extensionName).'_pi1';
}
$GLOBALS['TCA']['tt_content']['types'][$cType]['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types'][$cType]['subtypes_excludelist'][$pluginSignature] = 'select_key,pages';

if ($versionAsInt < 12000000) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:'.$extKey.'/Configuration/FlexForms/flexform.old.xml',
    );
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:'.$extKey.'/Configuration/FlexForms/flexform.xml',
        $cType
    );
}

