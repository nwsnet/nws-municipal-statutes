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

use Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\Controller\Area;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides functions for creating menus and reading out areas
 *
 * @package    Infodienste Rest-API (Local law)
 * @subpackage nws_municipal_statutes
 *
 */
class JurisdictionFinder extends AbstractJurisdictionFinder
{
    /**
     * @var Area
     */
    protected $area;

    /**
     * Initializes the FullRest Api for the Area object
     *
     * @return Area|object
     */
    public function area()
    {
        if (empty($this->area)) {
            $this->area = GeneralUtility::makeInstance(Area::class, $this->config);
        }
        return $this->area;
    }

    /**
     * Detects all possible areas recursive
     *
     * @param array $searchAreas
     * @param $recursive
     * @return array|mixed
     */
    public function getAreasRecursiveByAreas(array $searchAreas, $recursive)
    {
        $cacheIdentifier = md5(
            $this->jsonEncode($searchAreas) . '-' . __FUNCTION__
        );
        if ($this->cacheInstance->has($cacheIdentifier)) {
            $areas = $this->cacheInstance->get($cacheIdentifier);
        } else {
            $areas = array();
            foreach ($searchAreas as $key => $value) {
                $searchItem = $this->area()->findById($key)->getJsonDecode();
                $areas['results'][$searchItem['id']] = $searchItem;
                $this->findAreasBySearchItem($searchItem, $recursive, $areas);
            }
            $this->cacheInstance->set($cacheIdentifier, $areas, array('callRestApi'));
        }
        return $areas;
    }

    /**
     * Finds all related areas recursive to a search entry
     *
     * @param array $searchItem
     * @param integer $recursive
     * @param array $areas
     */
    protected function findAreasBySearchItem(array $searchItem, $recursive, array &$areas)
    {
        $filter = array(
            'parentId' => $searchItem['id']
        );
        $dataDown = $this->area()->find($filter)->getJsonDecode();
        if ($dataDown['count'] > 0) {
            foreach ($dataDown['results'] as $items) {
                if ($items['object']['areaType']['key'] !== 'GEMEINDETEIL') {
                    $areas['results'][$items['object']['id']] = $items['object'];
                    $this->findAreasBySearchItem($items['object'], 0, $areas);
                } else {
                    $areas['results'][$items['object']['id']] = $items['object'];
                }
            }
        } else {
            $areas['results'][$searchItem['id']] = $searchItem;
        }
        $areas['stopId'] = $searchItem['id'];
        if ($recursive > 0) {
            for ($i = $recursive; $i > 0; $i--) {
                $searchItem = $this->area()->findById($searchItem['parentId'])->getJsonDecode();
                $filter = array(
                    'parentId' => $searchItem['id']
                );
                $dataUp = $this->area()->find($filter)->getJsonDecode();
                if ($dataUp['count'] > 0) {
                    foreach ($dataUp['results'] as $items) {
                        if ($items['object']['areaType']['key'] !== 'GEMEINDETEIL' && array_key_exists($items['object']['id'],
                                $areas) === false) {
                            $areas['results'][$items['object']['id']] = $items['object'];
                            $this->findAreasBySearchItem($items['object'], 0, $areas);
                        } elseif (array_key_exists($items['object']['id'], $areas) === false) {
                            $areas['results'][$items['object']['id']] = $items['object'];
                        }
                    }
                } else {
                    $areas['results'][$searchItem['id']] = $searchItem;
                }
                $areas['stopId'] = $searchItem['id'];
            }
        }
    }

    /**
     * Creates a hierarchical menu with area and legislator
     *
     * @param array $legislator
     * @return array
     */
    public function getTreeMenu(array $legislator)
    {
        $cacheIdentifier = md5(
            $this->jsonEncode($legislator) . '-' . __FUNCTION__
        );
        if ($this->cacheInstance->has($cacheIdentifier)) {
            $treeMenu = $this->cacheInstance->get($cacheIdentifier);
        } else {
            $treeMenu = array();
            foreach ($legislator['results'] as $data) {
                foreach ($data['object']['areas'] as $area) {
                    $result = $this->area()->findById($area['id'])->getJsonDecode();
                    if (!$this->recursiveArraySearch($result['id'], $treeMenu)) {
                        if ($this->recursiveArraySearch($result['parentId'], $treeMenu)) {
                            $treeMenu = $this->setAvailableParentResult($result, $treeMenu);
                        } else {
                            $treeMenu = $this->setParentResult($result, $treeMenu);
                        }
                    }
                }
            }
            $treeMenu = $this->mergeAreaWithLegislator($treeMenu, $legislator);
            $treeMenu = $this->setDisplayName($treeMenu);
            $this->cacheInstance->set($cacheIdentifier, $treeMenu, array('callRestApi'));
        }
        return $treeMenu;
    }

    /**
     * Assigns the associated child to the existing parents
     *
     * @param array $result
     * @param array $treeMenu
     * @return array
     */
    protected function setAvailableParentResult(array $result, array $treeMenu)
    {
        $id = $result['id'];
        $parentId = $result['parentId'];
        foreach ($treeMenu as $key => $value) {
            if ($key == $parentId) {
                $treeMenu[$key]['child'][$id] = $result;
            } elseif (is_array($treeMenu[$key]['child'])) {
                $treeMenu[$key]['child'] = $this->setAvailableParentResult($result, $treeMenu[$key]['child']);
            }
        }
        return $treeMenu;
    }

    /**
     * Finds the child's parent and places it
     *
     * @param array $result
     * @param array $treeMenu
     * @param array $parents
     * @return array
     */
    protected function setParentResult(array $result, array $treeMenu, $parents = array())
    {
        $id = $result['id'];
        $parentId = $result['parentId'];
        $ags = $result['ags'];
        if ($this->recursiveArraySearch($parentId, $treeMenu)) {
            if (!$this->recursiveArraySearch($id, $treeMenu)) {
                $treeMenu = $this->setAvailableParentResult($result, $treeMenu);
            }
            unset($parents[$result['id']]);
        } else {
            if (count($parents) == 0) {
                $parents[$result['id']] = $result;
            }
            if ($ags != $this->agsKey && $id != $this->stopId) {
                $data = $this->area()->findById($result['parentId'])->getJsonDecode();
                $parents[$data['id']] = $data;
                return $this->setParentResult($data, $treeMenu, $parents);
            }
        }
        if (count($parents) > 1) {
            $parents = array_reverse($parents, true);
        }
        foreach ($parents as $key => $value) {
            $id = $value['id'];
            $parentId = $value['parentId'];
            $ags = $value['ags'];
            if (!$this->recursiveArraySearch($parentId, $treeMenu)) {
                if ($ags == $this->agsKey || $id == $this->stopId) {
                    $treeMenu[$id] = $value;
                }
            } else {
                $treeMenu = $this->setAvailableParentResult($value, $treeMenu);
            }
        }
        return $treeMenu;
    }

    /**
     * Reads out the legislators and adds them to the menu
     *
     * @param array $treeMenu
     * @param array $legislator
     * @return array
     */
    protected function mergeAreaWithLegislator(array $treeMenu, array $legislator)
    {
        foreach ($legislator['results'] as $data) {
            foreach ($data['object']['areas'] as $area) {
                $treeMenu = $this->setLegislatorToTreeMenu($area['id'], $data['object'], $treeMenu);
            }
        }
        return $treeMenu;
    }

    /**
     * Adds the legislature in the area
     *
     * @param $id
     * @param array $legislator
     * @param array $treeMenu
     * @return array
     */
    protected function setLegislatorToTreeMenu($id, array $legislator, array $treeMenu)
    {
        foreach ($treeMenu as $key => $value) {
            if ($id == $key) {
                $treeMenu[$key]['legislator'][$legislator['id']] = $legislator;
                break;
            } else {
                if (isset($treeMenu[$key]['child'])) {
                    $treeMenu[$key]['child'] = $this->setLegislatorToTreeMenu($id, $legislator,
                        $treeMenu[$key]['child']);
                }
            }
        }
        return $treeMenu;
    }

    /**
     * Changes the output name of a link based on the area typ
     *
     * @param array $treeMenu
     * @return array
     */
    protected function setDisplayName(array $treeMenu)
    {
        foreach ($treeMenu as $key => $value) {
            $displayName = isset($value['shortName']) ? $value['shortName'] : $value['name'];
            switch ($value['areaType']['key']) {
                case 'LANDKREIS':
                    $displayName = 'Kreis ' . $displayName;
                    break;
            }
            $treeMenu[$key] = $value;
            $treeMenu[$key]['displayName'] = $displayName;
            if (isset($treeMenu[$key]['child'])) {
                $treeMenu[$key]['child'] = $this->setDisplayName($treeMenu[$key]['child']);
            }
        }
        return $treeMenu;
    }
}