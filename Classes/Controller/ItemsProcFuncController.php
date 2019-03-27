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

namespace Nwsnet\NwsMunicipalStatutes\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ItemsProcFunc Controller for reading and providing alternative selection fields for the media elemete (Flexform)
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class ItemsProcFuncController extends AbstractController {

	/**
	 * ItemsProcFuncApiCall
	 *
	 * @var \Nwsnet\NwsMunicipalStatutes\Api\GetItemsProcFunc
	 */
	protected $apiCall;

	/**
	 * Patter for transmitted get parameter
	 *
	 * @var string
	 */
	private $arrayPattern = 'tx_nwsmunicipalstatutes_items1';

	/**
	 * @param \Nwsnet\NwsMunicipalStatutes\Api\GetItemsProcFunc $apiCallGet
	 */
	public function injectGetEvents(\Nwsnet\NwsMunicipalStatutes\Api\GetItemsProcFunc $apiCall) {
		$this->apiCall = $apiCall;
	}

	/**
	 * Deploy category selection
	 *
	 * @return string (json-array)
	 */
	public function showCategoriesAction() {
		//check whether there is an error with the API
		if ($this->apiCall->hasExceptionError()) {
			$error = $this->apiCall->getExceptionError();
			$exception = new \stdClass;
			$exception->categories[0]['name'] = $error['message'];
			$exception->categories[0]['id'] = 0;
			return $this->apiCall->jsonEncode($exception);
		}
		$params = '';
		if (isset($this->settings['transmit'])) {
			$params = $this->settings['transmit'];
			$params['localeAll'] = $GLOBALS['TSFE']->config['config']["locale_all"];
		}
		$request = $this->request->getArguments();
		if (count($request) == 0) {
			// Extract parameters based on the extension patter
			$request = GeneralUtility::_GP($this->arrayPattern);
		}
		$categoriesShowData = $this->apiCall->getCategoriesData($request, $params);
		return $categoriesShowData;
	}

	/**
	 * Deploying the province selection
	 *
	 * @return string (json-array)
	 */
	public function showProvincesAction() {
		//check whether there is an error with the API
		if ($this->apiCall->hasExceptionError()) {
			$error = $this->apiCall->getExceptionError();
			$exception = new \stdClass;
			$exception->provinces[0]['name'] = $error['message'];
			$exception->provinces[0]['id'] = 0;
			return $this->apiCall->jsonEncode($exception);
		}
		$params = '';
		if (isset($this->settings['transmit'])) {
			$params = $this->settings['transmit'];
			$params['localeAll'] = $GLOBALS['TSFE']->config['config']["locale_all"];
		}
		$request = $this->request->getArguments();
		if (count($request) == 0) {
			// Extract parameters based on the extension patter
			$request = GeneralUtility::_GP($this->arrayPattern);
		}

		$statesShowData = $this->apiCall->getProvincesData($request, $params);
		return $statesShowData;
	}
}