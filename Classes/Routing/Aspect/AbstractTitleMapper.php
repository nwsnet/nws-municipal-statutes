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

namespace Nwsnet\NwsMunicipalStatutes\Routing\Aspect;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Initializes the ability to read titles from an API interface and make them available for the link
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class AbstractTitleMapper
{
    /**
     * maximum length of the title link
     */
    const MAX_ALIAS_LENGTH = 100;

    /**
     * Slugs are cached temporarily, to avoid repeated requests to the API,
     * on slug generation. The resolving is currently uncached though.
     */
    const SLUG_CACHE_LIFETIME = 3600;

    /**
     * Is set via $settings
     *
     * @var
     */
    protected $maxLength;

    /**
     * vendorName
     *
     * @var string
     */
    protected $vendorName = 'Nwsnet';

    /**
     * extensionName
     *
     * @var string
     */
    protected $extensionName = 'NwsMunicipalStatutes';

    /**
     * pluginName
     *
     * @var string
     */
    protected $pluginName = 'Pi1';

    /**
     * Used only for sanitizing. "generate" won't work!
     *
     * @var SlugHelper
     */
    protected $slugHelper;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * configurationBootstrap
     *
     * @var array
     */
    private $configurationBootstrap;

    /**
     * bootstrap
     *
     * @var object
     */
    private $bootstrap;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * @var array
     */
    private $setting;

    /**
     * @var ContentObjectRenderer
     */
    private $contentObject;

    /**
     * Request object
     * @var array
     */
    private $controllerAlias = array(
        'LocalLaw' => 'Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController',
    );

    /**
     * AbstractTitleMapper constructor.
     * @param array $settings
     * @throws InvalidConfigurationTypeException
     */
    public function __construct(array $settings)
    {
        $this->maxLength = !isset($settings['maxLength']) ? $settings['maxLength'] : self::MAX_ALIAS_LENGTH;
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        // we'll just reuse the default page cache to store our slug cache
        $this->cache = $cacheManager->getCache('cache_pages');

        /** @var SlugHelper slugHelper */
        $this->slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            $settings['tableName'],
            $settings['fieldName'],
            $settings['configuration']
        );

        // Read existing extbase configuration
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var ConfigurationManager $configurationManager */
        $this->configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $this->setting = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $this->contentObject = $this->configurationManager->getContentObject();

        //set the configuration
        $this->configurationBootstrap = array(
            'vendorName' => $this->vendorName,
            'extensionName' => $this->extensionName,
            'pluginName' => $this->pluginName,

        );
        /** @var Bootstrap $bootstrap */
        $this->bootstrap = $objectManager->get(Bootstrap::class);
    }

    /**
     * Get the title over the interface
     *
     * @param array $arguments
     * @return string|null
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     * @throws InvalidExtensionNameException
     * @throws InfiniteLoopException
     */
    protected function getTitle(array $arguments)
    {
        //Workaround for url reverse conversion
        if (!isset($GLOBALS['TSFE'])) {
            if (isset($arguments['evid'])) {
                return (string)$arguments['evid'];
            } elseif (isset($arguments['sid'])) {
                return (string)$arguments['sid'];
            } elseif (isset($arguments['fid'])) {
                return (string)$arguments['fid'];
            } else {
                return "0";
            }
        }
        $controller = isset($arguments['controller']) ? $arguments['controller'] : 'Events';
        $action = isset($arguments['action']) ? $arguments['action'] : 'showTitle';

        /**
         * Initialize Extbase bootstap
         */
        $this->configurationBootstrap['controller'] = $controller;
        $this->configurationBootstrap['action'] = $action;
        $this->bootstrap->initialize($this->configurationBootstrap);
        $this->bootstrap->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
            $GLOBALS['TSFE']);
        /**
         * Build the request
         */
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        /** @var Request $request */
        $request = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Request');
        $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($versionAsInt < 9999999) {
            $request->setControllerVendorName($this->vendorName);
        } else {
            $request->setControllerAliasToClassNameMapping($this->controllerAlias);
        }
        $request->setControllerName($controller);
        $request->setcontrollerExtensionName($this->extensionName);
        $request->setPluginName($this->pluginName);
        $request->setControllerActionName($action);
        $request->setArguments($arguments);
        /** @var ResponseInterface $response */
        $response = $objectManager->get('TYPO3\CMS\Extbase\Mvc\ResponseInterface');
        /** @var Dispatcher $dispatcher */
        $dispatcher = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');
        $dispatcher->dispatch($request, $response);
        $title = $response->getContent();
        //fallback for removing "/"
        $title = str_replace('/', '', $title);

        // Initialization of the original extbase configuration
        if (!empty($this->contentObject)) {
            $this->configurationManager->setConfiguration($this->setting);
            $this->configurationManager->setContentObject($this->contentObject);
        }

        if (isset($title) && !empty($title)) {
            return empty($title) ? null : $title;
        }
        return null;
    }
}
