<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Dirk Meinke <typo3@die-netzwerkstatt.de>
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

namespace Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw;

use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Category;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\LegalNorm;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Legislator;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Structure;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides functions for the compilation of regulations, legislators and structures
 *
 * @package    Infodienste Rest-API (Local law)
 * @subpackage nws_municipal_statutes
 *
 */
class LocalLaw extends AbstractLocalLaw
{

	/**
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Category
	 */
	protected $category;

	/**
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\LegalNorm
	 */
	protected $legalNorm;

	/**
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Legislator
	 */
	protected $legislator;

	/**
	 * @var \Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller\Structure
	 */
	protected $structure;

	/**
	 * Initializes the FullRest Api for the Category object
	 *
	 * @return Category|object
	 */
	public function category()
	{
		if (empty($this->category)) {
			$this->category = GeneralUtility::makeInstance(Category::class, $this->config);
		}
		return $this->category;
	}

	/**
	 * Initializes the FullRest Api for the LegalNorm object
	 *
	 * @return LegalNorm|object
	 */
	public function legalNorm()
	{
		if (empty($this->legalNorm)) {
			$this->legalNorm = GeneralUtility::makeInstance(LegalNorm::class, $this->config);
		}
		return $this->legalNorm;
	}

	/**
	 * Initializes the FullRest Api for the Legislator object
	 *
	 * @return Legislator|object
	 */
	public function legislator()
	{
		if (empty($this->legislator)) {
			$this->legislator = GeneralUtility::makeInstance(Legislator::class, $this->config);
		}
		return $this->legislator;
	}

	/**
	 * Initializes the FullRest Api for the Structure object
	 *
	 * @return Structure|object
	 */
	public function structure()
	{
		if (empty($this->structure)) {
			$this->structure = GeneralUtility::makeInstance(Structure::class, $this->config);
		}
		return $this->structure;
	}

	/**
	 * Finds the legislators have deposited the rules
	 *
	 * @param array $legislators
	 * @return array
	 */
	public function getLegalNormByLegislator(array $legislators)
	{

		$cacheIdentifier = md5(
			$this->jsonEncode($legislators) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$legislators = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$count = $legislators['count'];
			foreach ($legislators['results'] as $key => $legislator) {
				$filter = array(
					'legislatorId' => $legislator['object']['id'],
					'selectAttributes' => array(
						'id'
					)
				);
				$data = $this->legalNorm()->find($filter)->getJsonDecode();
				if ($data['count'] == 0) {
					$count -= 1;
					unset($legislators['results'][$key]);
				}
			}
			$legislators['count'] = $count;
			$this->cacheInstance->set($cacheIdentifier, $legislators, array('callRestApi'));

		}
		return $legislators;
	}

	/**
	 * Adds the legal norms to the corresponding structure
	 *
	 * @param integer $legislatorId
	 * @param array $legalNorm
	 * @return array
	 */
	public function getStructureByAllLegalNorm($legislatorId, array $legalNorm)
	{
		$cacheIdentifier = md5(
			$legislatorId . '-' . $this->jsonEncode($legalNorm) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$structure = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$structure = array();
			$filter = array(
				'legislatorId' => $legislatorId
			);
			$result = $this->structure()->find($filter)->getJsonDecode();
			if ($result['count'] == 1) {
				$structure = $result['results'][0]['object'];
			}
			foreach ($structure['structure']['subStructurNodes'] as $key => $data) {
				$dataCheck = $this->getAllLegalNormByStructureId($data['id'], $legalNorm);
				if (count($dataCheck) > 0) {
					$structure['structure']['subStructurNodes'][$key]['legalNorm'] = $dataCheck;
				} else {
					unset($structure['structure']['subStructurNodes'][$key]);
				}
			}
			$structure['count'] = $legalNorm['count'];
			$this->cacheInstance->set($cacheIdentifier, $structure, array('callRestApi'));
		}
		return $structure;
	}

	/**
	 * Finds the structure id of a legal norm and returns the legal norm
	 *
	 * @param integer $structureId
	 * @param array $legalNorm
	 * @return array
	 */
	protected function getAllLegalNormByStructureId($structureId, array $legalNorm)
	{
		$result = array();
		foreach ($legalNorm['results'] as $data) {
			if ($this->recursiveArraySearch($structureId, $data['object']['structureNodes']) !== false) {
				$result[$data['object']['id']] = $data['object'];
			}
		}
		return $result;
	}

	/**
	 * Adds the structure to the single legal norm
	 *
	 * @param $legislatorId
	 * @param array $legalNorm
	 * @return array
	 */
	public function getLegalNormWithStructure($legislatorId, array $legalNorm)
	{
		$cacheIdentifier = md5(
			$legislatorId . '-' . $this->jsonEncode($legalNorm) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$legalNorm = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$structure = array();
			$filter = array(
				'legislatorId' => $legislatorId
			);
			$result = $this->structure()->find($filter)->getJsonDecode();
			if ($result['count'] == 1) {
				$structure = $result['results'][0]['object'];
			}
			foreach ($legalNorm['structureNodes'] as $structureNodes) {
				foreach ($structure['structure']['subStructurNodes'] as $data) {
					if ($data['id'] == $structureNodes['id']) {
						$legalNorm['structure'] = $data;
					}
				}
			}
			$legalNorm['name'] = $structure['name'];

			$this->cacheInstance->set($cacheIdentifier, $legalNorm, array('callRestApi'));
		}
		return $legalNorm;
	}
}