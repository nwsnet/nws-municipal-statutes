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

namespace Nwsnet\NwsMunicipalStatutes\Session;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class for the storage of user data
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class UserSession implements SingletonInterface
{
	/**
	 * sessionStorage
	 *
	 * @var SessionStorage
	 */
	private $sessionStorage;

	/**
	 * construct for the storage of user data
	 *
	 * @param SessionStorage $sessionStorage
	 *
	 */
	public function __construct(SessionStorage $sessionStorage)
	{
		$this->sessionStorage = $sessionStorage;
	}

	/**
	 * Returns the object stored in the session of the user with the choice of search criteria
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getSearch()
	{
		if ($this->sessionStorage->has('search')) {
			return $this->sessionStorage->getObject('search');
		} else {
			return null;
		}
	}

	/**
	 * Saves the object in the session of the user with the choice of search criteria
	 *
	 * @param object $value search selection
	 *
	 * @return void
	 */
	public function saveSearch(array $value)
	{
		$this->sessionStorage->storeObject($value, 'search');
	}

	/**
	 * Deletes the object in the session of the user with the choice of search criteria
	 *
	 * @return void
	 */
	public function cleanSearch()
	{
		if ($this->sessionStorage->has('search')) {
			$this->sessionStorage->clean('search');
		}
	}

	/**
	 * Returns the object stored in the session of the user of which page you came to the current page
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getReferrer()
	{
		if ($this->sessionStorage->has('referrer')) {
			return $this->sessionStorage->getObject('referrer');
		}
		return null;
	}

	/**
	 * Saves the object in the session of the user of which page you came to the current page
	 *
	 * @param object $value data pages
	 *
	 * @return void
	 */
	public function saveReferrer(array $value)
	{
		$this->sessionStorage->storeObject($value, 'referrer');
	}

	/**
	 * Deletes the object in the session of the user of which page you came to the current page
	 *
	 * @return void
	 */
	public function cleanReferrer()
	{
		if ($this->sessionStorage->has('referrer')) {
			$this->sessionStorage->clean('referrer');
		}
	}
}