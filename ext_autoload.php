<?php
// Register necessary classes with autoloader
$extensionClassesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nws_municipal_statutes') . 'Classes/';
return array(
    'tx_nwsmunicipalstatutes_hooks_itemsprocfunc' => $extensionClassesPath . 'Hooks/ItemsProcFunc.php',
    'SimpleHtmlDom' => $extensionClassesPath . 'Dom/SimpleHtmlDom.php',
);
