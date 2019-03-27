<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Meinke <typo3@die-netzwerkstatt.de>
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

namespace Nwsnet\NwsMunicipalStatutes\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

/**
 * Class ItemsProcFunc, provide alternative selection fields for media elements
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class ItemsProcFunc {

	/**
	 * vendorName
	 *
	 * @var string
	 */
	private $vendorName = 'Nwsnet';

	/**
	 * extensionName
	 *
	 * @var string
	 */
	private $extensionName = 'NwsMunicipalStatutes';

	/**
	 * pluginName
	 *
	 * @var string
	 */
	private $pluginName = 'Items1';

	/**
	 * configuration
	 *
	 * @var array
	 */
	private $configuration;

	/**
	 * bootstrap
	 *
	 * @var array
	 */
	private $bootstrap;

	/**
	 * controllerActions
	 *
	 * @var array
	 */
	private $controllerActions = array(  // Allowed controller action combinations
		'ItemsProcFunc' => 'showCategories,showProvinces',
	);

	/**
	 * Initialize Extbase
	 *
	 * @see Bootstrap::run()
	 */
	public function __construct() {
		//set the configuration
		$this->configuration = array(
			'vendorName' => $this->vendorName,
			'extensionName' => $this->extensionName,
			'pluginName' => $this->pluginName,

		);
		//set the default allowed controller action combinations
		foreach ($this->controllerActions as $controllerName => $actions) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['modules'][$this->pluginName]['controllers'][$controllerName] = array(
				'actions' => GeneralUtility::trimExplode(',', $actions)
			);
		}
		$this->bootstrap = new Bootstrap();
	}

	/**
	 * Items Proc function read the categories to extend the selection in the plugin
	 *
	 *
	 * @param array &$params configuration array
	 *
	 * @return void
	 */
	public function readCategories(array &$params) {
		$authCode = '';
		//read and provide flexform
		if (isset($params['row']['pi_flexform']) && !empty($params['row']['pi_flexform'])) {
			$data = GeneralUtility::xml2array($params['row']['pi_flexform']);
			$authCode = $this->pi_getFFvalue($data, 'settings.enableAuthenticationCode', 'sDEF');
		} elseif (isset($params['row']['uid']) && isset($params['table']) && is_numeric($params['row']['uid'])) {
			$pi_flexform = $this->getPiFlexformFromTable($params['table'], $params['row']['uid']);
			$data = GeneralUtility::xml2array($pi_flexform);
			$authCode = $this->pi_getFFvalue($data, 'settings.enableAuthenticationCode', 'sDEF');
		}
		//test for double call
		$post = GeneralUtility::_GP('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName));
		//first call
		if (!isset($post['jsonCategories']) || empty($post['jsonCategories']) || $params['config']['action'] != $post['action']) {
			unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['enableAuthenticationCode'] = $request['settings']['enableAuthenticationCode'] = $authCode;
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $params['config']['controller'];
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $params['config']['action'];
			//For TYPO3 9.5 put query parameters in the backend
			if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
				$queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
				$queryParams['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)] = $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)];
				$GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withQueryParams($queryParams);
			}
			$this->configuration['controller'] = $params['config']['controller'];
			$this->configuration['action'] = $params['config']['action'];
			$this->configuration = array_merge($this->configuration, $request);
			//start of Extbase bootstrap program
			$json = $this->bootstrap->run('', $this->configuration);

			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['jsonCategories'] = addslashes($json);
			$items = json_decode($json, TRUE);
			if (!empty($items) && is_array($items)) {
				foreach ($items['categories'] as $item) {
					$params['items'][] = array($item['name'], $item['id']);
				}
			}
			//second call
		} else {
			if (isset($post['jsonCategories']) && !empty($post['jsonCategories'])) {
				$json = $post['jsonCategories'];
				$items = json_decode($json, TRUE);
				if (!empty($items) && is_array($items)) {
					foreach ($items['categories'] as $item) {
						$params['items'][] = array($item['name'], $item['id']);
					}
				}
			}
			if (isset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)])) {
				unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
			}
		}
	}

	/**
	 * Items Proc function read the provinces to extend the selection in the plugin
	 *
	 * @param array &$params configuration array
	 *
	 * @return void
	 */
	public function readProvinces(array &$params) {
		$authCode = '';
		//read and provide flexform
		if (isset($params['row']['pi_flexform']) && !empty($params['row']['pi_flexform'])) {
			$data = GeneralUtility::xml2array($params['row']['pi_flexform']);
			$authCode = $this->pi_getFFvalue($data, 'settings.enableAuthenticationCode', 'sDEF');
		} elseif (isset($params['row']['uid']) && isset($params['table']) && is_numeric($params['row']['uid'])) {
			$pi_flexform = $this->getPiFlexformFromTable($params['table'], $params['row']['uid']);
			$data = GeneralUtility::xml2array($pi_flexform);
			$authCode = $this->pi_getFFvalue($data, 'settings.enableAuthenticationCode', 'sDEF');
		}
		//test for double call
		$post = GeneralUtility::_GP('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName));
		//first call
		if (!isset($post['jsonProvinces']) || empty($post['jsonProvinces']) || $params['config']['action'] != $post['action']) {
			unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['enableAuthenticationCode'] = $request['settings']['enableAuthenticationCode'] = $authCode;
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $params['config']['controller'];
			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $params['config']['action'];
			//For TYPO3 9.5 put query parameters in the backend
			if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
				$queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
				$queryParams['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)] = $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)];
				$GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withQueryParams($queryParams);
			}
			$this->configuration['controller'] = $params['config']['controller'];
			$this->configuration['action'] = $params['config']['action'];
			$this->configuration = array_merge($this->configuration, $request);
			//start of Extbase bootstrap program
			$json = $this->bootstrap->run('', $this->configuration);

			$_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['jsonStates'] = addslashes($json);
			$items = json_decode($json, TRUE);
			if (!empty($items) && is_array($items)) {
				foreach ($items['provinces'] as $item) {
					$params['items'][] = array($item['name'], $item['id']);
				}
			}
			//second call
		} else {
			if (isset($post['jsonProvinces']) && !empty($post['jsonProvinces'])) {
				$json = $post['jsonProvinces'];
				$items = json_decode($json, TRUE);
				if (!empty($items) && is_array($items)) {
					foreach ($items['provinces'] as $item) {
						$params['items'][] = array($item['name'], $item['id']);
					}
				}
			}
			if (isset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)])) {
				unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
			}
		}
	}

	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param array  $T3FlexForm_array FlexForm data
	 * @param string $fieldName        Field name to extract. Can be given like
	 *                                 "test/el/2/test/el/field_templateObject" where each part will dig a level deeper
	 *                                 in the FlexForm data.
	 * @param string $sheet            Sheet pointer, eg. "sDEF
	 * @param string $lang             Language pointer, eg. "lDEF
	 * @param string $value            Value pointer, eg. "vDEF
	 *
	 * @return string|NULL The content.
	 */
	public function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF') {
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray)) {
			return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
		}
		return NULL;
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param array  $sheetArray   Multidimensiona array, typically FlexForm contents
	 * @param array  $fieldNameArr Array where each value points to a key in the FlexForms content - the input array
	 *                             will have the value returned pointed to by these keys. All integer keys will not
	 *                             take their integer counterparts, but rather traverse the current position in the
	 *                             array an return element number X (whether this is right behavior is not settled
	 *                             yet...)
	 * @param string $value        Value for outermost key, typ. "vDEF" depending on language.
	 *
	 * @return mixed The value, typ. string.
	 * @access private
	 * @see    pi_getFFvalue()
	 */
	public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value) {
		$tempArr = $sheetArray;
		foreach ($fieldNameArr as $k => $v) {
			if (MathUtility::canBeInterpretedAsInteger($v)) {
				if (is_array($tempArr)) {
					$c = 0;
					foreach ($tempArr as $values) {
						if ($c == $v) {
							$tempArr = $values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}

	/**
	 * Read the Flex form from the database
	 *
	 * @param string  $table
	 * @param integer $uid
	 *
	 * @return string $pi_flexform
	 */
	protected function getPiFlexformFromTable($table, $uid) {
		$pi_flexform = '';
		if (isset($GLOBALS['TYPO3_DB'])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pi_flexform', $table, 'uid=' . $uid);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		} else {
			/** @var \TYPO3\CMS\Core\Database\ConnectionPool $queryBuilder */
			$queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($table);
			$res = $queryBuilder->select('pi_flexform')
				->from('tt_content')
				->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
				->groupBy('uid')
				->execute();
			$row = $res->fetch();
		}
		if (isset($row['pi_flexform']) && !empty($row['pi_flexform'])) {
			$pi_flexform = $row['pi_flexform'];
		}
		return $pi_flexform;
	}
}