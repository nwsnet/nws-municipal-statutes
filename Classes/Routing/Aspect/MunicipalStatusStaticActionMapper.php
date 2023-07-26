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

use InvalidArgumentException;
use TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;

/**
 * Title of the event for the link to the list view
 *
 * @package    TYPO3
 * @subpackage nws_council_system
 *
 */
class MunicipalStatusStaticActionMapper extends AbstractTitleMapper implements PersistedMappableAspectInterface,
                                                                               StaticMappableAspectInterface
{
    /**
     * @var string
     */
    protected $controller;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $argument;

    /**
     * MunicipalStatusStaticActionMapper constructor.
     * @param array $settings
     * @throws InvalidConfigurationTypeException
     */
    public function __construct(array $settings)
    {
        $settings['tableName'] = 'notable';
        $settings['fieldName'] = 'nofield';
        parent::__construct($settings);
        $controller = $settings['controller'] ?? null;
        $action = $settings['action'] ?? null;
        $argument = $settings['argument'] ?? [];
        $plugin = $settings['plugin'] ?? null;

        if (!is_string($controller)) {
            throw new InvalidArgumentException('controller must be string', 1634128608);
        }
        if (!is_string($action)) {
            throw new InvalidArgumentException('action must be be string', 1634128608);
        }
        if (!is_string($argument)) {
            throw new InvalidArgumentException('arguemnt must be string', 1634128608);
        }


        $this->controller = $controller;
        $this->action = $action;
        $this->argument = $argument;
        $this->pluginName = $plugin ?? 'Pi1';
    }

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
        $cacheKey = md5('nws-static-action-mapper-'.$this->controller.$this->action.$this->argument.$value);
        if ($this->cache->has($cacheKey)) {
            return (string)$this->cache->get($cacheKey);
        }

        $arguments = array(
            'controller' => $this->controller,
            'action' => $this->action,
            $this->argument => $value,
        );
        $title = $this->getTitle($arguments, $this->argument);
        if ($title === null) {
            return $value;
        }
        $title = str_replace('/', '-', $title);
        $slug = $this->slugHelper->sanitize($title);
        $slug = mb_substr($slug, 0, $this->maxLength).'-'.$value;
        $slug = $this->slugHelper->sanitize($slug);
        $this->cache->set($cacheKey, $slug, array('calRegionalMapApi'), self::SLUG_CACHE_LIFETIME);

        return $slug;
    }

    /**
     * Determines the ID and checks if the event still exists
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
        if (!$id = $this->getIdByString($value)) {
            return null;
        }
        $cacheKey = md5('nws-static-action-mapper-'.$this->controller.$this->action.$this->argument.$value);
        if ($this->cache->has($cacheKey)) {
            return (string)$id;
        }

        // Test if the id actually exists.
        // Should be done to properly display 404 pages, and to avoid cache flooding.
        $arguments = array(
            'controller' => $this->controller,
            'action' => $this->action,
            $this->argument => $id,
        );
        $title = $this->getTitle($arguments, $this->argument);
        if ($title === null) {
            return null; // -> 404
        }

        return $id;
    }
}
