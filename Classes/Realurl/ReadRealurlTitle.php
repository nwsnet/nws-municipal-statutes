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

namespace Nwsnet\NwsMunicipalStatutes\Realurl;

use DmitryDulepov\Realurl\Configuration\ConfigurationReader;
use DmitryDulepov\Realurl\Encoder\UrlEncoder;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class for the creation of the link title
 *
 * @package    realurl
 * @subpackage nws_jurisdictionfinder_sh
 *
 */
class ReadRealurlTitle
{
    const MAX_ALIAS_LENGTH = 100;
    /**
     * vendorName
     *
     * @var string
     */
    private $vendorName = 'Nwsnet';

    /**
     * extensionName
     *
     * @var string
     */
    private $extensionName = 'NwsMunicipalStatutes';

    /**
     * pluginName
     *
     * @var string
     */
    private $pluginName = 'Pi1';

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
     * ApiCall
     *
     * @var LocalLaw
     */
    protected $apiCall;

    /**
     * realurl
     *
     * @var object
     */
    private $ref;

    /**
     * @var TypoScriptFrontendController
     */
    protected $tsfe;

    /**
     * @var ConfigurationReader
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $getVarKey;

    /**
     * @var array
     */
    protected $originalUrlParameters = array();

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $setting;

    /**
     * @var ContentObjectRenderer
     */
    protected $contentObject;

    /**
     * Initialize Extbase
     *
     * @throws InvalidConfigurationTypeException
     * @see \TYPO3\CMS\Extbase\Core\Bootstrap::run()
     */
    public function __construct()
    {
        //set the configuration
        $this->configurationBootstrap = array(
            'vendorName' => $this->vendorName,
            'extensionName' => $this->extensionName,
            'pluginName' => $this->pluginName,

        );
        $this->bootstrap = new Bootstrap();
        $this->tsfe = $GLOBALS['TSFE'];
        // Read existing extbase configuration
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var ConfigurationManager $configurationManager */
        $this->configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $this->setting = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $this->contentObject = $this->configurationManager->getContentObject();
    }

    /**
     * @param LocalLaw $apiCall
     */
    public function injectApiLocalLaw(LocalLaw $apiCall)
    {
        $this->apiCall = $apiCall;
    }

    /**
     * Providing the legislator name for the link title
     *
     * @param array $params tx_realurl
     * @param object $ref tx_realurl
     *
     * @return string NULL|$title
     */
    public function getLegislatorTitle(&$params, $ref)
    {
        /** @var UrlEncoder $ref */
        $this->ref = $ref;
        if (method_exists($ref, 'getConfiguration')) {
            $this->configuration = $ref->getConfiguration();
        } else {
            $this->configuration = GeneralUtility::makeInstance('DmitryDulepov\\Realurl\\Configuration\\ConfigurationReader',
                ConfigurationReader::MODE_DECODE);
        }
        if (method_exists($ref, 'getOriginalUrlParameters')) {
            $this->originalUrlParameters = $ref->getOriginalUrlParameters();
        }
        $this->getVarKey = $params['setup']['GETvar'];

        if (isset($params['value'])) {
            if ($params['value']) {
                if ($params['decodeAlias']) {
                    return $this->getIdByAlias($params['setup'], $params['value'], __FUNCTION__);
                } else {
                    return $this->getAliasById($params['setup'], $params['value'], __FUNCTION__);
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Providing the legal norm name for the link title
     *
     * @param array $params tx_realurl
     * @param object $ref \DmitryDulepov\Realurl\Encoder\UrlEncoder
     *
     * @return string NULL|$title
     */
    public function getLegalNormTitle(&$params, $ref)
    {
        /** @var UrlEncoder $ref */
        $this->ref = $ref;
        if (method_exists($ref, 'getConfiguration')) {
            $this->configuration = $ref->getConfiguration();
        } else {
            $this->configuration = GeneralUtility::makeInstance('DmitryDulepov\\Realurl\\Configuration\\ConfigurationReader',
                ConfigurationReader::MODE_DECODE);
        }
        if (method_exists($ref, 'getOriginalUrlParameters')) {
            $this->originalUrlParameters = $ref->getOriginalUrlParameters();
        }
        $this->getVarKey = $params['setup']['GETvar'];

        if (isset($params['value'])) {
            if ($params['value']) {
                if ($params['decodeAlias']) {
                    return $this->getIdByAlias($params['setup'], $params['value'], __FUNCTION__);
                } else {
                    return $this->getAliasById($params['setup'], $params['value'], __FUNCTION__);
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Providing the legislator name using the API for the link title
     *
     * @param int $param ID of the record
     *
     * @return string NULL|$title
     */
    protected function getLegislatorTitleData($param)
    {
        $controller = 'LocalLaw';
        $action = 'showTitleLegislator';
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $controller;
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $action;
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['legislator'] = intval($param);
        $this->configurationBootstrap['controller'] = $controller;
        $this->configurationBootstrap['action'] = $action;
        //TYPO3 8.7 must be switched off the cHash validate
        $request['features']['requireCHashArgumentForActionArguments'] = 0;
        $this->configurationBootstrap = array_merge($this->configurationBootstrap, $request);
        //start of Extbase bootstrap program
        $title = $this->bootstrap->run('', $this->configurationBootstrap);
        // Initialization of the original extbase configuration
        if (!empty($this->contentObject)) {
            $this->configurationManager->setConfiguration($this->setting);
            $this->configurationManager->setContentObject($this->contentObject);
        }

        if (isset($title) && !empty($title)) {
            return $title;
        }
        return null;
    }

    /**
     * Providing the legal norm name using the API for the link title
     *
     * @param integer $param ID of the record
     *
     * @return string NULL|$title
     */
    protected function getLegalNormTitleData($param)
    {
        $controller = 'LocalLaw';
        $action = 'showTitle';
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $controller;
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $action;
        $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['legalnorm'] = ($param == '*') ? $param : intval($param);
        $this->configurationBootstrap['controller'] = $controller;
        $this->configurationBootstrap['action'] = $action;
        //TYPO3 8.7 must be switched off the cHash validate
        $request['features']['requireCHashArgumentForActionArguments'] = 0;
        $this->configurationBootstrap = array_merge($this->configurationBootstrap, $request);
        $title = $this->bootstrap->run('', $this->configurationBootstrap);
        // Initialization of the original extbase configuration
        if (!empty($this->contentObject)) {
            $this->configurationManager->setConfiguration($this->setting);
            $this->configurationManager->setContentObject($this->contentObject);
        }

        if (isset($title) && !empty($title)) {
            return $title;
        }
        return null;
    }

    /**
     * Database search for the alias with the ID
     *
     *
     * @param array $setup Configuration of look-up table, field names etc.
     * @param string $value Value to match field in database to.
     * @param string $setMethod Method to the determine the alias
     *
     * @return    string        Result value of lookup. If no value was found the $value is returned.
     */
    protected function getAliasById($setup, $value, $setMethod)
    {
        $aliasBaseValue = '';

        //Cache of the existing POST data before the title query is performed
        $postData = array();
        if (isset($_POST)) {
            $postData = $_POST;
            unset($_POST);
        }
        if ($setMethod) {
            $setMethod .= 'Data';
            if (method_exists($this, $setMethod)) {
                $aliasBaseValue = $this->$setMethod($value);
            }
        }
        //Override the new POST data using the cached data
        $_POST = $postData;

        $maxAliasLengthLength = isset($setup['maxLength']) ? (int)$setup['maxLength'] : self::MAX_ALIAS_LENGTH;
        $aliasBaseValue = $this->tsfe->csConvObj->substr('utf-8', $aliasBaseValue, 0, $maxAliasLengthLength);

        if ($aliasBaseValue) {
            $aliasBaseValue = $this->ref->cleanUpAlias($setup, $aliasBaseValue);
            $value = $this->createUniqueAlias($aliasBaseValue, $value);
        }
        // In case no value was found in translation we return the incoming value. It may be argued that this is not a good idea but generally this can be avoided by using the "useUniqueCache" principle which will ensure unique translation both ways.
        return $value;
    }

    /**
     * Database search for the ID with the Alias
     *
     *
     * @param array $setup Configuration of look-up table, field names etc.
     * @param string $value Value to match field in database to.
     * @param string $setMethod Method to the determine the alias
     *
     * @return    string        Result value of lookup. If no value was found the $value is returned.
     */
    protected function getIdByAlias($setup, $value, $setMethod)
    {
        $match = [];
        if (!preg_match('/^([\p{L}0-9\/-]+-)?(\d+)$/', $value, $match)) {
            return null;
        }
        return (int)$match[2];
    }

    /**
     * Creates a unique alias.
     *
     * @param       $newAliasValue
     * @param       $idValue
     *
     * @return string
     */
    protected function createUniqueAlias($newAliasValue, $idValue)
    {
        return empty($newAliasValue) ? $idValue : $newAliasValue . '-' . $idValue;
    }
}