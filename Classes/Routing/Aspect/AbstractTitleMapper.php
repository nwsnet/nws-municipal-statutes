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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Initializes the ability to read titles from an API interface and make them available for the link
 *
 * @package    TYPO3
 * @subpackage nws_council_system
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
     * @var int
     */
    protected int $maxLength;

    /**
     * vendorName
     *
     * @var string
     */
    protected string $vendorName = 'Nwsnet';

    /**
     * extensionName
     *
     * @var string
     */
    protected string $extensionName = 'NwsMunicipalStatutes';

    /**
     * pluginName
     *
     * @var string
     */
    protected string $pluginName = 'Pi1';

    /**
     * Patter for transmitted get parameter
     *
     * @var string
     */
    protected string $arrayPattern = 'tx_nwsmunicipalstatutes_pi1';

    /**
     * Used only for sanitizing. "generate" won't work!
     *
     * @var SlugHelper
     */
    protected $slugHelper;

    /**
     * @var FrontendInterface
     */
    protected FrontendInterface $cache;

    /**
     * configurationBootstrap
     *
     * @var array
     */
    private array $configurationBootstrap;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * @var array
     */
    protected array $settings;

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
    private array $controllerAlias = array(
        'LocalLaw' => 'Nwsnet\NwsMunicipalStatutes\Controller\LocalLawController',
    );

    /**
     * AbstractTitleMapper constructor.
     * @param array $settings
     * @throws InvalidConfigurationTypeException
     * @throws NoSuchCacheException
     */
    public function __construct(array $settings)
    {
        $this->maxLength = !isset($settings['maxLength']) ? $settings['maxLength'] : self::MAX_ALIAS_LENGTH;
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        // we'll just reuse the default page cache to store our slug cache
        $this->cache = $cacheManager->getCache('nws_municipal_statutes');

        $this->slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            $settings['tableName'],
            $settings['fieldName'],
            $settings['configuration']
        );

        // Read existing extbase configuration
        if (class_exists(ObjectManager::class)) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var ConfigurationManager $configurationManager */
            if (isset($GLOBALS['TSFE'])) {
                /** @var ConfigurationManager $configurationManager */
                $this->configurationManager = $objectManager->get(ConfigurationManager::class);
                $this->setting = $this->configurationManager->getConfiguration(
                    ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
                );
                $this->contentObject = $this->configurationManager->getContentObject();
            }
        } else {
            if (isset($GLOBALS['TSFE'])) {
                /** @var ConfigurationManager $configurationManager */
                $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
                $this->setting = $this->configurationManager->getConfiguration(
                    ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
                );
                if (method_exists($this->configurationManager, 'getContentObject')) {
                    $this->contentObject = $this->configurationManager->getContentObject();
                } else {
                    $this->contentObject = $GLOBALS['TYPO3_REQUEST']->getAttribute('currentContentObject');
                }
            }
        }

        //set the configuration
        $this->configurationBootstrap = array(
            'vendorName' => $this->vendorName,
            'extensionName' => $this->extensionName,
            'pluginName' => $this->pluginName,

        );
    }

    /**
     * Get the title over the interface
     *
     * @param array $arguments
     * @param string $argumentName
     * @return string|null
     * @throws InfiniteLoopException
     * @throws \ReflectionException
     */
    protected function getTitle(array $arguments, string $argumentName): ?string
    {
        //Workaround for url reverse conversion
        if (!isset($GLOBALS['TSFE'])) {
            if (isset($arguments[$argumentName])) {
                return (string)$arguments[$argumentName];
            } else {
                return "0";
            }
        }
        if (class_exists(ObjectManager::class)) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var Bootstrap $bootstrap */
            $bootstrap = clone $objectManager->get(Bootstrap::class);
            /** @var Request $request */
            $request = clone $objectManager->get(Request::class);
        } else {
            /** @var Bootstrap $bootstrap */
            $bootstrap = clone GeneralUtility::makeInstance(Bootstrap::class);
            $extbaseRequestParameters = GeneralUtility::makeInstance(ExtbaseRequestParameters::class);
            $extbaseRequestParameters->setControllerAliasToClassNameMapping($this->controllerAlias);
            /** @var ServerRequestInterface $request */
            $request = clone $GLOBALS['TYPO3_REQUEST'];
            $request = $request->withAttribute('extbase', $extbaseRequestParameters);
            /** @var Request $request */
            $request = GeneralUtility::makeInstance(Request::class, $request);
        }

        $controller = $arguments['controller'] ?? 'LocalLaw';
        $action = $arguments['action'] ?? 'showTitle';

        /**
         * Initialize Extbase bootstrap
         */
        $this->configurationBootstrap['controller'] = $controller;
        $this->configurationBootstrap['action'] = $action;
        $this->configurationBootstrap['switchableControllerActions'][$controller][] = $action;
        $bootstrap->initialize($this->configurationBootstrap, $request);
        if (method_exists($bootstrap, 'setContentObjectRenderer')) {
            /** @var ContentObjectRenderer $contentObjectRenderer */
            $contentObjectRenderer = GeneralUtility::makeInstance(
                'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
                $GLOBALS['TSFE']
            );
            $bootstrap->setContentObjectRenderer($contentObjectRenderer);
        } else {
            $bootstrap->cObj = GeneralUtility::makeInstance(
                'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
                $GLOBALS['TSFE']
            );
        }

        if (defined('TYPO3_version')) {
            $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        } else {
            $version = VersionNumberUtility::getNumericTypo3Version();
            $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger($version);
        }

        if ($versionAsInt < 12000000) {
            $request->setControllerAliasToClassNameMapping($this->controllerAlias);
            $request->setControllerName($controller);
            $request->setcontrollerExtensionName($this->extensionName);
            $request->setPluginName($this->pluginName);
            $request->setControllerActionName($action);
            $request->setArguments($arguments);
        } else {
            $request = $request
                ->withControllerName($controller)
                ->withControllerExtensionName($this->extensionName)
                ->withPluginName($this->pluginName)
                ->withControllerActionName($action)
                ->withArguments($arguments)
                ->withQueryParams(
                    [
                        sprintf(
                            $this->arrayPattern,
                            strtolower($this->configurationBootstrap['pluginName'])
                        ) => $arguments,
                    ]
                );
        }


        if ($versionAsInt < 12000000) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var Dispatcher $dispatcher */
            $dispatcher = $objectManager->get('TYPO3\CMS\Extbase\Mvc\Dispatcher');
            $reflection = new \ReflectionMethod($dispatcher, 'dispatch');
            if ($reflection->getNumberOfParameters() === 1) {
                $response = $dispatcher->dispatch($request);
                $title = $response->getBody()->__toString();
            } else {
                /** @var ResponseInterface $response */
                $response = $objectManager->get('TYPO3\CMS\Extbase\Mvc\ResponseInterface');
                $dispatcher->dispatch($request, $response);
                $title = $response->getContent();
            }
        } else {
            /** @var PageArguments $routing */
            $routing = $request->getAttribute('routing');
            $pageArguments = new PageArguments(
                $routing->getPageId(),
                $routing->getPageType(),
                $routing->getRouteArguments(),
                $routing->getStaticArguments(),
                $request->getQueryParams()
            );
            $request = $request->withAttribute('routing', $pageArguments);
            $title = $bootstrap->run('', $this->configurationBootstrap, $request);
        }

        //fallback for removing "/"
        $title = str_replace('/', '', $title);

        // Initialization of the original extbase configuration
        if (!empty($this->contentObject)) {
            $this->configurationManager->setConfiguration($this->setting);
            $this->configurationManager->setContentObject($this->contentObject);
        }

        return !empty($title) ? $title : null;
    }

    /**
     * Finds the id at the end of the string with delimiter "-"
     *
     * @param string $value
     * @return string|null
     */
    protected function getIdByString(string $value): ?string
    {
        $result = null;
        if (strpos($value, '-') !== false) {
            $ids = GeneralUtility::trimExplode('-', $value);
            $id = array_slice($ids, -1);
            $result = !empty($id[0] ?? null) ? $id[0] : null;
        } else {
            if (is_numeric($value)) {
                $result = $value;
            } elseif (is_numeric(substr($value, 1))) {
                $result = $value;
            }
        }

        return $result;
    }
}

