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

use Nwsnet\NwsMunicipalStatutes\Dom\Converter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;

/**
 * Events Controller for the delivery of event data
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class LocalLawController extends AbstractController
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
	public function injectApiLocalLaw(\Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw $apiLocalLaw)
	{
		$this->apiLocalLaw = $apiLocalLaw;
	}

	/**
	 * @param \Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder $apiJurisdictionFinder
	 */
	public function injectApiJurisdictionFinder(
		\Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder $apiJurisdictionFinder
	) {
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

	public function listAction()
	{
		if ($this->apiLocalLaw->legislator()->findAll()->hasExceptionError()) {
			$error = $this->apiLocalLaw->legislator()->getExceptionError();
			throw new UnsupportedRequestTypeException($error['message'], $error['code']);
		}
		$legislator = $this->apiLocalLaw->getLegalNormByLegislator($this->apiLocalLaw->legislator()->getJsonDecode());
		$treeMenu = $this->apiJurisdictionFinder->getTreeMenu($legislator);
		$legalNorm = array();
		if ($this->request->hasArgument('legislator')) {
			$filter = array(
				'legislatorId' => $this->request->getArgument('legislator'),
				'selectAttributes' => array(
					'id',
					'categories',
					'structureNodes',
					'longTitle',
					'jurisPromulgationDate',
					'jurisAmendDate',
					'jurisEnactmentFrom'
				),
				'sortAttribute' => 'longTitle'
			);
			if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
				$error = $this->apiLocalLaw->legalNorm()->getExceptionError();
				throw new UnsupportedRequestTypeException($error['message'], $error['code']);
			}
			$legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
			$legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm($this->request->getArgument('legislator'),
				$legalNorm);


		}

		$this->view->assign('treeMenu', $treeMenu);
		$this->view->assign('legalNorm', $legalNorm);
	}

	public function singlelistAction()
	{
		$legalNorm = array();
		if ($this->settings['legislatorId']) {
			$filter = array(
				'legislatorId' => $this->settings['legislatorId'],
				'selectAttributes' => array(
					'id',
					'categories',
					'structureNodes',
					'longTitle',
					'jurisPromulgationDate',
					'jurisAmendDate',
					'jurisEnactmentFrom'
				),
				'sortAttribute' => 'longTitle'
			);
			if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
				$error = $this->apiLocalLaw->legalNorm()->getExceptionError();
				throw new UnsupportedRequestTypeException($error['message'], $error['code']);
			}
			$legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
			$legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm($this->settings['legislatorId'], $legalNorm);


		}
		$this->view->assign('legalNorm', $legalNorm);
	}


	public function showAction()
	{
		$legalNormId = 0;
		if ($this->request->hasArgument('legalnorm')) {
			$legalNormId = $this->request->getArgument('legalnorm');
		}

		if ($this->apiLocalLaw->legalNorm()->findById($legalNormId)->hasExceptionError()) {
			$error = $this->apiLocalLaw->legislator()->getExceptionError();
			throw new UnsupportedRequestTypeException($error['message'], $error['code']);
		}
		$legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
		$legislatorId = $legalNorm['legislator']['id'];

		$legalNorm = $this->apiLocalLaw->getLegalNormWithStructure($legislatorId, $legalNorm);

		$converter = GeneralUtility::makeInstance(Converter::class);
		$legalNorm['parseContent'] = $converter->getContentArray($legalNorm['content']);


		$this->view->assign('legalNorm', $legalNorm);
	}

	/**
	 * Providing the legal norm name for the page and link title generation
	 *
	 * @return string NULL|$legalNorm['longTitle']
	 */
	public function showTitleAction()
	{

		$request = $this->request->getArguments();
		$legalNormId = $request['legalnorm'];
		$filter = array(
			'selectAttributes' => array(
				'id',
				'longTitle'
			)
		);
		if ($this->apiLocalLaw->legalNorm()->findById($legalNormId, $filter)->hasExceptionError()) {
			return '';
		}
		$legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();

		if (isset($legalNorm['longTitle']) && !empty($legalNorm['longTitle'])) {
			return $legalNorm['longTitle'];
		}
		return '';
	}


}