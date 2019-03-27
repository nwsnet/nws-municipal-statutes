<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
/**
 * Number of the user group: */

$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['apiKey'] = $_EXTCONF['apiKey'] ? $_EXTCONF['apiKey'] : '';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Nwsnet.' . $_EXTKEY,
	'Pi1',
	array(
		'LocalLaw' => 'list,singlelist,search,show,showTitle,teaser',
	),
	// non-cacheable actions
	array(
		'LocalLaw' => 'list,singlelist,search',

	)
);
if (TYPO3_MODE === 'BE') {
	if (class_exists('\TYPO3\CMS\Core\Imaging\IconRegistry')) {
		$icons = [
			'ext-nws-municipal-statutes-wizard-icon' => 'ce_wiz.svg',
		];
		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		foreach ($icons as $identifier => $path) {
			$iconRegistry->registerIcon(
				$identifier,
				\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
				['source' => 'EXT:nws_municipal_statutes/Resources/Public/Icons/' . $path]
			);
		}
	}
}
//Add TSconfig
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:nws_municipal_statutes/Configuration/TSconfig/pageTSConfig.ts">');
//Add Eid
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nwsMunicipalStatutesDispatcher'] = \Nwsnet\NwsMunicipalStatutes\Eid\Dispatcher::class . '::processRequest';

// Caching framework
if (!is_array($GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$_EXTKEY])) {
	$GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$_EXTKEY] = array();
	$GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$_EXTKEY]['frontend'] = 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend';
	$GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$_EXTKEY]['groups'] = array('pages');
	// Cache for 12 hour
	$GLOBALS['TYPO3_CONF_VARS'] ['SYS']['caching']['cacheConfigurations'][$_EXTKEY]['options'] = array('defaultLifetime' => 43200);
}

