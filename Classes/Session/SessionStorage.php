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

namespace Nwsnet\NwsMunicipalStatutes\Session;

use LogicException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class for the storage of user data
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class SessionStorage implements SingletonInterface
{
    /**
     * Session namespace
     *
     * @const SESSIONNAMESPACE
     */
    const SESSIONNAMESPACE = 'tx_nwsmunicipalstatutes';

    /**
     * Returns the object stored in the user´s session
     *
     * @param string $key
     *
     * @return object the stored object
     */
    public function get($key)
    {
        $sessionData = $this->getFrontendUser()->getKey('ses', self::SESSIONNAMESPACE . $key);
        if ($sessionData == '') {
            throw new LogicException('No value for key found in session ' . $key);
        }
        return $sessionData;
    }

    /**
     * checks if object is stored in the user´s session
     *
     * @param string $key
     *
     * @return boolean
     */
    public function has($key)
    {
        $sessionData = $this->getFrontendUser()->getKey('ses', self::SESSIONNAMESPACE . $key);
        if ($sessionData == '') {
            return false;
        }
        return true;
    }

    /**
     * Writes something to storage
     *
     * @param string $key
     * @param string $value
     *
     * @return    void
     */
    public function set($key, $value)
    {
        $this->getFrontendUser()->setKey('ses', self::SESSIONNAMESPACE . $key, $value);
        $this->getFrontendUser()->storeSessionData();
    }

    /**
     * Writes a object to the session if the key is empty it used the classname
     *
     * @param object $object
     * @param string $key
     *
     * @return    void
     */
    public function storeObject($object, $key = null)
    {
        if (is_null($key)) {
            $key = get_class($object);
        }
        $this->set($key, serialize($object));
    }

    /**
     * Writes something to storage
     *
     * @param string $key
     *
     * @return    object
     */
    public function getObject($key)
    {
        return unserialize($this->get($key));
    }

    /**
     * Cleans up the session: removes the stored object from the PHP session
     *
     * @param string $key
     *
     * @return    void
     */
    public function clean($key)
    {
        $this->getFrontendUser()->setKey('ses', self::SESSIONNAMESPACE . $key, null);
        $this->getFrontendUser()->storeSessionData();
    }

    /**
     * Gets a frontend user which is taken from the global registry or as fallback from TSFE->fe_user.
     *
     * @return    FrontendUserAuthentication    The current extended frontend user
     *                                                                             object
     * @throws    LogicException
     */
    protected function getFrontendUser()
    {
        if ($GLOBALS ['TSFE']->fe_user) {
            return $GLOBALS ['TSFE']->fe_user;
        }
        throw new LogicException ('No Frontentuser found in session!');
    }
}