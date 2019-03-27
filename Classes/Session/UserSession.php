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

namespace Nwsnet\NwsMunicipalStatutes\Session;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class for the storage of user data
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class UserSession implements SingletonInterface {
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
	public function __construct(SessionStorage $sessionStorage) {
		$this->sessionStorage = $sessionStorage;
	}

	/**
	 * Returns the object stored in the session of the user with the choice of page number
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getBrowse() {
		if ($this->sessionStorage->has('browse')) {
			return $this->sessionStorage->getObject('browse');
		}
		return NULL;
	}

	/**
	 * Saves the object in the session of the user with the choice of page number
	 *
	 * @param object $value page number
	 *
	 * @return void
	 */
	public function saveBrowse(array $value) {
		$this->sessionStorage->storeObject($value, 'browse');
	}

	/**
	 * Deletes the object in the session of the user with the choice of page number
	 *
	 * @return void
	 */
	public function cleanBrowse() {
		if ($this->sessionStorage->has('browse')) {
			$this->sessionStorage->clean('browse');
		}
	}

	/**
	 * Returns the object stored in the session of the user with the choice of search criteria
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getSearch() {
		if ($this->sessionStorage->has('search')) {
			return $this->sessionStorage->getObject('search');
		} else {
			return NULL;
		}
	}

	/**
	 * Saves the object in the session of the user with the choice of search criteria
	 *
	 * @param object $value search selection
	 *
	 * @return void
	 */
	public function saveSearch(array $value) {
		$this->sessionStorage->storeObject($value, 'search');
	}

	/**
	 * Deletes the object in the session of the user with the choice of search criteria
	 *
	 * @return void
	 */
	public function cleanSearch() {
		if ($this->sessionStorage->has('search')) {
			$this->sessionStorage->clean('search');
		}
	}

	/**
	 * Returns the object stored in the session of the user with the choice of day selection
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getSearchDay() {
		if ($this->sessionStorage->has('searchday')) {
			return $this->sessionStorage->getObject('searchday');
		} else {
			return NULL;
		}
	}

	/**
	 * Saves the object in the session of the user with the choice of day selection
	 *
	 * @param object $value day selection
	 *
	 * @return void
	 */
	public function saveSearchDay(array $value) {
		$this->sessionStorage->storeObject($value, 'searchday');
	}

	/**
	 * Deletes the object in the session of the user with the choice of day selection
	 *
	 * @return void
	 */
	public function cleanSearchDay() {
		if ($this->sessionStorage->has('searchday')) {
			$this->sessionStorage->clean('searchday');
		}
	}

	/**
	 * Returns the object stored in the session of the user of which page you came to the current page
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getReferrer() {
		if ($this->sessionStorage->has('referrer')) {
			return $this->sessionStorage->getObject('referrer');
		}
		return NULL;
	}

	/**
	 * Saves the object in the session of the user of which page you came to the current page
	 *
	 * @param object $value data pages
	 *
	 * @return void
	 */
	public function saveReferrer(array $value) {
		$this->sessionStorage->storeObject($value, 'referrer');
	}

	/**
	 * Deletes the object in the session of the user of which page you came to the current page
	 *
	 * @return void
	 */
	public function cleanReferrer() {
		if ($this->sessionStorage->has('referrer')) {
			$this->sessionStorage->clean('referrer');
		}
	}

	/**
	 * Returns the object that is stored in the user's session with the selection of the organizer
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getOrganizer() {
		if ($this->sessionStorage->has('organizer')) {
			return $this->sessionStorage->getObject('organizer');
		}
		return NULL;
	}

	/**
	 * Saves the object in the user's session of the organizer data
	 *
	 * @param object $value organizer data
	 *
	 * @return void
	 */
	public function saveOrganizer(array $value) {
		$this->sessionStorage->storeObject($value, 'organizer');
	}

	/**
	 * Deletes the object in the session of the user of the organizer data
	 *
	 * @return void
	 */
	public function cleanOrganizer() {
		if ($this->sessionStorage->has('organizer')) {
			$this->sessionStorage->clean('organizer');
		}
	}

	/**
	 * Returns the object that is stored in the user's session, whether they have come from the password page
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getPassword() {
		if ($this->sessionStorage->has('password')) {
			return $this->sessionStorage->getObject('password');
		}
		return NULL;
	}

	/**
	 * Saves the object in the user's session when they come from the password page
	 *
	 * @param object $value data password page
	 *
	 * @return void
	 */
	public function savePassword(array $value) {
		$this->sessionStorage->storeObject($value, 'password');
	}

	/**
	 * Deletes the object in the user's session when they come from the password page
	 *
	 * @return void
	 */
	public function cleanPassword() {
		if ($this->sessionStorage->has('password')) {
			$this->sessionStorage->clean('password');
		}
	}

	/**
	 * Returns the object stored in the session of the user with the choice of month criteria
	 *
	 * @return NULL|object Nwsnet\NwsMunicipalStatutes\Session\SessionStorage getObject()
	 */
	public function getMonthSelection() {
		if ($this->sessionStorage->has('month')) {
			return $this->sessionStorage->getObject('month');
		} else {
			return NULL;
		}
	}

	/**
	 * Saves the object in the session of the user with the choice of month criteria
	 *
	 * @param object $value search selection
	 *
	 * @return void
	 */
	public function saveMonthSelection(array $value) {
		$this->sessionStorage->storeObject($value, 'month');
	}

	/**
	 * Deletes the object in the session of the user with the choice of month criteria
	 *
	 * @return void
	 */
	public function cleanMonthSelection() {
		if ($this->sessionStorage->has('month')) {
			$this->sessionStorage->clean('month');
		}
	}
}