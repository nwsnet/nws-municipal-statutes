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

namespace Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\Controller;

use Nwsnet\NwsMunicipalStatutes\RestApi\RestClient;

/**
 * Providing the call routes in the interface for legislator
 *
 * @package    Infodienste Rest-API (Local law)
 * @subpackage nws_municipal_statutes
 */
class Legislator extends RestClient
{
    const URI_GET_FIND = '/legislator/find';
    const URI_GET_FIND_BY_KEY = '/legislator​/findByKey​/{key}';
    const URI_GET_FIND_BY_ID = '/legislator/{id}';

    /**
     * Legislator constructor.
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        parent::setConfiguration($config);
    }

    /**
     * Use this function to find all normators.
     *
     * @param array $filter
     * @param array $data
     *
     * @return mixed
     */
    public function findAll($filter = array(), $data = array())
    {
        $dataMerge = array();
        if (count($data) == 0) {
            $filter['limit'] = isset($filter['limit']) ? $filter['limit'] : 100;
        } else {
            $dataMerge = $data;
        }
        if ($this->getData(self::URI_GET_FIND, $filter)->hasExceptionError() === false) {
            $data = json_decode($this->getResult(), true);
            $count = isset($data['count']) ? $data['count'] : 0;
            $data = array_merge_recursive($dataMerge, $data);
            if ($count >= $filter['limit']) {
                $filter['offset'] = $filter['offset'] + $filter['limit'] + 1;
                $this->findAll($filter, $data);
                return $this;
            }
            if (isset($data['count']) && is_array($data['count'])) {
                $count = 0;
                foreach ($data['count'] as $value) {
                    $count += $value;
                }
                $data['count'] = $count;
            }
        }
        if (count($data) > 0) {
            $this->setResult($this->jsonEncode($data));
        }

        return $this;
    }

    /**
     * Use this function to find normators.
     *
     * @param array $filter
     *
     * @return mixed
     */
    public function find($filter = array())
    {
        return $this->getData(self::URI_GET_FIND, $filter);
    }

    /**
     * Determines a normator by the key.
     *
     * @param string $key
     * @param array $filter
     *
     * @return mixed
     */
    public function findByKey($key, $filter = array())
    {
        return $this->getData(str_replace('{key}', $key, self::URI_GET_FIND_BY_KEY), $filter);
    }

    /**
     * Determines a normator by ID.
     *
     * @param integer $id
     * @param array $filter
     *
     * @return mixed
     */
    public function findById($id, $filter = array())
    {
        return $this->getData(str_replace('{id}', $id, self::URI_GET_FIND_BY_ID), $filter);
    }
}