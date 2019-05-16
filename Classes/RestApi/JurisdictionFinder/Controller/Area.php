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

namespace Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\Controller;

use Nwsnet\NwsMunicipalStatutes\RestApi\RestClient;

/**
 * Finds the appropriate areas
 *
 * @package    Infodienste Rest-API (jurisdictional finder)
 * @subpackage nws_municipal_statutes
 */
class Area extends RestClient
{
	const URI_GET_FIND = '/area/find';
	const URI_GET_FIND_BY_ID = '/area/{id}';

	/**
	 * Area constructor.
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		parent::setConfiguration($config);
	}

	/**
	 * Use this function to find areas.
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
	 * Finds an area based on its id.
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