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

use Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw;

/**
 * ItemsProcFunc Controller for reading and providing alternative selection fields for the media elemete (Flexform)
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class ItemsProcFuncController extends AbstractController
{
	/**
	 * localLawApiCall get data
	 *
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw
	 */
	protected $apiLocalLaw;

	/**
	 * jurisdictionFinderApiCall get data
	 *
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder
	 */
	protected $apiJurisdictionFinder;

	/**
	 * @param \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw $apiLocalLaw
	 */
	public function injectApiLocalLaw(LocalLaw $apiLocalLaw)
	{
		$this->apiLocalLaw = $apiLocalLaw;
	}

	/**
	 * @param \Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder $apiJurisdictionFinder
	 */
	public function injectApiJurisdictionFinder(JurisdictionFinder $apiJurisdictionFinder)
	{
		$this->apiJurisdictionFinder = $apiJurisdictionFinder;
	}

	/**
	 * deactivates flashmessages -> they are being generated for validation errrors for example
	 *
	 * @see ActionController::getErrorFlashMessage()
	 */
	protected function getErrorFlashMessage()
	{
		return false;
	}

	/**
	 * Read the legislators and put them to the selection
	 *
	 * @return string
	 */
	public function showLegislatorAction()
	{
		$filter = array(
			'sortAttribute' => array('name')
		);
		if ($this->apiLocalLaw->legislator()->findAll($filter)->hasExceptionError()) {
			$error = $this->apiLocalLaw->legislator()->getExceptionError();
			$exception = new \stdClass;
			$exception->legislator[0]['name'] = $error['message'];
			$exception->legislator[0]['id'] = 0;
			return $this->apiLocalLaw->jsonEncode($exception);
		}
		$items = array();
		$legislator = $this->apiLocalLaw->legislator()->getJsonDecode();
		if ($legislator['count'] > 0) {
			foreach ($legislator['results'] as $item) {
				$items['legislator'][] = array(
					'id' => $item['object']['id'],
					'name' => $item['object']['name']
				);
			}
		}
		return $this->apiLocalLaw->jsonEncode($items);
	}
}