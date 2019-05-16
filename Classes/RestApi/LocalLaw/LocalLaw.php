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
	 * Finds the legislator with the ID and returns the full record
	 *
	 * @param array $items
	 * @return array
	 */
	public function getLegalNormByLegislatorId(array $items)
	{

		$cacheIdentifier = md5(
			$this->jsonEncode($items) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$legislators = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$count = $items['count'];
			foreach ($items['results'] as $legislator) {
				$filter = array(
					'legislatorId' => $legislator,
					'selectAttributes' => array(
						'id'
					)
				);
				$data = $this->legalNorm()->find($filter)->getJsonDecode();
				if ($data['count'] == 0) {
					$count -= 1;
				} else {
					$legislators['results'][]['object'] = $this->legislator()->findById($legislator)->getJsonDecode();
				}
			}
			$legislators['count'] = $count;
			$this->cacheInstance->set($cacheIdentifier, $legislators, array('callRestApi'));

		}
		return $legislators;
	}

	/**
	 * Merges the legislators with their territories and only pays back the existing legislators
	 *
	 * @param array $areas
	 * @return mixed
	 */
	public function mergeLegislatorByAreas(array $areas)
	{
		$cacheIdentifier = md5(
			$this->jsonEncode($areas) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$legislators = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$filter = array(
				'sortAttribute' => array('name')
			);
			$legislatorsItems = $this->legislator()->findAll($filter)->getJsonDecode();
			$count = isset($legislatorsItems['count']) && $legislatorsItems['count'] > 0 ? $legislatorsItems['count'] : 0;
			if (isset($legislatorsItems['count']) && $legislatorsItems['count'] > 0) {
				foreach ($legislatorsItems['results'] as $legislator) {
					if ($this->checkAreaId($legislator['object']['areas'], $areas)) {
						$filter = array(
							'legislatorId' => $legislator['object']['id'],
							'selectAttributes' => array(
								'id'
							)
						);
						$data = $this->legalNorm()->find($filter)->getJsonDecode();
						if ($data['count'] == 0) {
							$count -= 1;
						} else {
							$legislators['results'][]['object'] = $legislator['object'];
						}
					} else {
						$count -= 1;
					}
				}
			}
			$legislators['count'] = $count;
			$this->cacheInstance->set($cacheIdentifier, $legislators, array('callRestApi'));

		}
		return $legislators;
	}

	/**
	 * Picked the areas based on the legislator ID
	 *
	 * @param array $items
	 * @return array|mixed
	 */
	public function getAreasByLegislatorId(array $items)
	{

		$cacheIdentifier = md5(
			$this->jsonEncode($items) . '-' . __FUNCTION__
		);
		if ($this->cacheInstance->has($cacheIdentifier)) {
			$areas = $this->cacheInstance->get($cacheIdentifier);
		} else {
			$areas = array();
			foreach ($items['results'] as $legislator) {
				$data = $this->legislator()->findById($legislator)->getJsonDecode();
				foreach ($data['areas'] as $value) {
					$areas[$value['id']] = $legislator;
				}
			}
			$this->cacheInstance->set($cacheIdentifier, $areas, array('callRestApi'));

		}
		return $areas;
	}

	/**
	 * Check if the area is present in the selection
	 *
	 * @param array $area
	 * @param array $areas
	 * @return bool
	 */
	protected function checkAreaId(array $area, array $areas)
	{
		foreach ($area as $value) {
			if (array_key_exists($value['id'], $areas)) {
				return true;
			}
		}
		return false;
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
	 * Determines the structure name and saves it to the legal norm
	 *
	 * @param $legislatorId
	 * @param array $legalNorm
	 * @return array|mixed
	 */
	public function getLegalNormByStructure($legislatorId, array $legalNorm)
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
				$structure = $result['results'][0]['object']['structure']['subStructurNodes'];
				$legalNorm['id'] = $result['results'][0]['object']['id'];
				$legalNorm['name'] = $result['results'][0]['object']['name'];
				$legalNorm['legislator'] = $result['results'][0]['object']['legislator'];

			}
			$count = 0;
			foreach ($legalNorm['results'] as $items) {
				$legalNorm['results'][$count] = $items['object'];
				$legalNorm['results'][$count]['structureNodes'] = $this->getStructureByLegalNorm($legalNorm['results'][$count]['structureNodes'],
					$structure);
				$count += 1;
			}
			//unset($legalNorm['results']);
			$this->cacheInstance->set($cacheIdentifier, $legalNorm, array('callRestApi'));
		}
		return $legalNorm;
	}

	/**
	 * Find the structure id and set the structure name
	 *
	 * @param array $structurNodes
	 * @param array $structure
	 * @return array
	 */
	protected function getStructureByLegalNorm(array $structurNodes, array $structure)
	{
		$result = array();
		foreach ($structurNodes as $structurNode) {
			foreach ($structure as $structureItem) {
				if ($structureItem['id'] == $structurNode['id']) {
					$result[] = !empty($structureItem['structureText']) ? $structureItem['structureText'] : $structureItem['structureNumber'];
				}
			}
		}
		return $result;
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