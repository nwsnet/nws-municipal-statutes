<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Dirk Meinke <typo3@die-netzwerkstatt.de>, die NetzWerkstatt GmbH & Co. KG
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

namespace Nwsnet\NwsMunicipalStatutes\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;

/**
 * Title of the legislator for the link to the list view
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class NwsLegislatorTitleMapper extends AbstractTitleMapper implements PersistedMappableAspectInterface,
                                                                      StaticMappableAspectInterface
{
    /**
     * Get the title and generate the link name
     *
     * @param string $value
     * @return string|null
     * @throws InfiniteLoopException
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     * @throws InvalidExtensionNameException
     */
    public function generate(string $value): ?string
    {
        $legislator = (int)$value;

        $cacheKey = 'nws-legislator-slug-' . $legislator;
        if ($this->cache->has($cacheKey)) {
            return (string)$this->cache->get($cacheKey);
        }

        $arguments = array(
            'controller' => 'LocalLaw',
            'action' => 'showTitleLegislator',
            'legislator' => $legislator
        );
        $title = $this->getTitle($arguments);
        if ($title === null) {
            return (int)$legislator;
        }

        $slug = $this->slugHelper->sanitize($title);
        $slug = mb_substr($slug, 0, $this->maxLength) . '-' . $legislator;
        $slug = $this->slugHelper->sanitize($slug);
        $this->cache->set($cacheKey, $slug, array('callLocalLawTitleApi'), self::SLUG_CACHE_LIFETIME);
        return $slug;
    }

    /**
     * Determines the ID and checks if the legislator still exists
     *
     * @param string $value
     * @return string|null
     * @throws InfiniteLoopException
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     * @throws InvalidExtensionNameException
     */
    public function resolve(string $value): ?string
    {
        $match = [];
        if (!preg_match('/^([\p{L}0-9\/-]+-)?(\d+)$/', $value, $match)) {
            return null;
        }
        $legislator = (int)$match[2];
        $cacheKey = 'nws-legislator-slug-' . $legislator;
        if ($this->cache->has($cacheKey)) {
            return (int)$legislator;
        }

        // Test if the id actually exists.
        // Should be done to properly display 404 pages, and to avoid cache flooding.
        $arguments = array(
            'controller' => 'LocalLaw',
            'action' => 'showTitleLegislator',
            'legislator' => $legislator
        );
        $title = $this->getTitle($arguments);
        if ($title === null) {
            return null; // -> 404
        }

        return $legislator;
    }
}
