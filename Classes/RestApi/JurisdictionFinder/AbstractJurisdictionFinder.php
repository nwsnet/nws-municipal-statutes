<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Dirk Meinke <typo3@die-netzwerkstatt.de>, die NetzWerkstatt GmbH & Co. KG
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

namespace Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class AbstractJurisdictionFinder for the initialization of FullRestApi
 *
 * @package    Infodienste Rest-API (Infodienste Rest-API (jurisdictional finder))
 * @subpackage nws_municipal_statutes
 */
class AbstractJurisdictionFinder
{
	/**
	 * $_EXTKEY
	 *
	 * @var string
	 */
	protected $extKey = 'nws_municipal_statutes';

	/**
	 * ConfigurationManagerInterface
	 *
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * Typoscript Settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Ext Template Settings
	 *
	 * @var array
	 */
	protected $extConf;

	/**
	 * Individual configuration for the FullRest Api calls
	 *
	 * @var array $config
	 */
	protected $config;

	/**
	 * Official municipality key for a federal state, for example (Schleswig-Holstein = 01)
	 *
	 * @var string $agsKey
	 */
	protected $agsKey = '01';

	/**
	 * Stop ID for creating the tree menu
	 *
	 * @var int
	 */
	protected $stopId = 0;

	/**
	 * cacheUtility
	 *
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $cacheInstance;

	/**
	 * Injects the Configuration Manager and is initializing the framework settings
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface An instance of the Configuration Manager
	 *
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
	{
		$this->configurationManager = $configurationManager;
		$config = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($config['settings'])) {
			$this->settings = $config['settings'];
		} else {
			$this->settings = $this->configurationManager->getConfiguration(
				ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
				GeneralUtility::underscoredToUpperCamelCase($this->extKey),
				'Pi1'
			);
		}

		$this->loadExtConf();
		// Set Api Url
		if (isset($this->extConf['jurisdictionFinderApiUrl'])) {
			$this->config['http'] = $this->checkApiUrl($this->extConf['jurisdictionFinderApiUrl']);
		}
		// Set Api Key
		if (isset($this->settings['apiKey']) && !empty($this->settings['apiKey'])) {
			$this->config['apiKey'] = $this->settings['apiKey'];
		} elseif (isset($this->extConf['apiKey'])) {
			$this->config['apiKey'] = $this->extConf['apiKey'];
		}
		// Set language header
		if (isset($GLOBALS['TSFE']->config['config']["locale_all"])) {
			$localeAll = strpos($GLOBALS['TSFE']->config['config']["locale_all"],
				'.') !== false ? substr($GLOBALS['TSFE']->config['config']["locale_all"], 0,
				strpos($GLOBALS['TSFE']->config['config']["locale_all"],
					'.')) : $GLOBALS['TSFE']->config['config']["locale_all"];
		} else {
			$localeAll = 'de_DE';
		}
		$this->config['additionalHeaders']['Accept-Language'] = $localeAll;

		if (isset($this->extConf['agsKey'])) {
			$this->agsKey = $this->extConf['agsKey'];
		}
		$this->initializeCache();
	}

	/**
	 * Initialization of the cache framework
	 *
	 * @see \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected function initializeCache()
	{
		/** @var \TYPO3\CMS\Core\Cache\CacheManager cacheInstance */
		$this->cacheInstance = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache($this->extKey);
	}

	/**
	 * Check if the last character is not a "/"
	 *
	 * @param $url
	 * @return string
	 */
	protected function checkApiUrl($url)
	{
		$lastCharacter = substr($url, -1);
		if ($lastCharacter == '/') {
			$url = substr($url, 0, strrpos($url, '/'));
		}
		return $url;
	}

	/**
	 * Loads the extConf
	 *
	 * @return void
	 */
	protected function loadExtConf()
	{
		//load the ext conf (ext_conf_template.txt)
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * Sets the area ID for tree menu creation
	 *
	 * @param string $stopId
	 */
	public function setStopId($stopId)
	{
		$this->stopId = $stopId;
	}


	/**
	 * Encode to the json representation
	 *
	 * @param object
	 *
	 * @return string
	 */
	public function jsonEncode($data)
	{
		return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	}

	/**
	 * Recursive search of an array to a value
	 *
	 * @param string $needle (serach string)
	 * @param array $haystack (to be searched)
	 *
	 * @return false|$current_key
	 */
	public function recursiveArraySearch($needle, $haystack)
	{
		foreach ($haystack as $key => $value) {
			$current_key = $key;
			if ($needle === $value OR (is_array($value) && $this->recursiveArraySearch($needle, $value) !== false)) {
				return $current_key;
			}
		}
		return false;
	}

}