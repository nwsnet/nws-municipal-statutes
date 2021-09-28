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

namespace Nwsnet\NwsMunicipalStatutes\Controller;

use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw;
use stdClass;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
     * @var LocalLaw
     */
    protected $apiLocalLaw;

    /**
     * @param LocalLaw $apiLocalLaw
     */
    public function injectApiLocalLaw(LocalLaw $apiLocalLaw)
    {
        $this->apiLocalLaw = $apiLocalLaw;
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
            $exception = new stdClass;
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

    /**
     * Reads the structure of a legislator and adds it to the selection
     *
     * @return string
     */
    public function showStructureAction()
    {
        if (isset($this->settings['legislatorId']) && !empty($this->settings['legislatorId'])) {
            $filter = array(
                'legislatorId' => $this->settings['legislatorId'],
                'sortAttribute' => array(
                    'name'
                )
            );

            if ($this->apiLocalLaw->structure()->find($filter)->hasExceptionError()) {
                $error = $this->apiLocalLaw->structure()->getExceptionError();
                $exception = new stdClass;
                $exception->structure[0]['name'] = $error['message'];
                $exception->structure[0]['id'] = 0;
                return $this->apiLocalLaw->jsonEncode($exception);
            }
            $items = array();
            $structure = $this->apiLocalLaw->structure()->getJsonDecode();
            if ($structure['count'] > 0) {
                foreach ($structure['results'] as $value) {
                    foreach ($value['object']['structure']['subStructurNodes'] as $item) {
                        $items['structure'][] = array(
                            'id' => $item['id'],
                            'name' => $item['structureText']
                        );
                    }
                }
            }
            return $this->apiLocalLaw->jsonEncode($items);
        } else {
            $items['structure'][] = array(
                'id' => 0,
                'name' => LocalizationUtility::translate('global.empty',
                    $this->extensionName)
            );
            return $this->apiLocalLaw->jsonEncode($items);
        }
    }
}