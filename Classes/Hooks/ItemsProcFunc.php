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

namespace Nwsnet\NwsMunicipalStatutes\Hooks;

use PDO;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ItemsProcFunc, provide alternative selection fields for media elements
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class ItemsProcFunc
{
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
    private $pluginName = 'Items1';

    /**
     * configuration
     *
     * @var array
     */
    private $configuration;

    /**
     * bootstrap
     *
     * @var array
     */
    private $bootstrap;

    /**
     * controllerActions
     *
     * @var array
     */
    private $controllerActions = array(  // Allowed controller action combinations
        'ItemsProcFunc' => 'showLegislator,showStructure',
    );

    /**
     * TYPO3 10.x controller class
     * @var string
     */
    private $controllerClass = "Nwsnet\NwsMunicipalStatutes\Controller\ItemsProcFuncController";

    /**
     * TYPO3 10.x controller alias
     * @var string
     */
    private $controllerAlias = "ItemsProcFunc";

    /**
     * Initialize Extbase
     *
     * @see Bootstrap::run()
     */
    public function __construct()
    {
        //set the configuration
        $this->configuration = array(
            'vendorName' => $this->vendorName,
            'extensionName' => $this->extensionName,
            'pluginName' => $this->pluginName,

        );
        $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($versionAsInt < 9999999) {
            //set the default allowed controller action combinations
            foreach ($this->controllerActions as $controllerName => $actions) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['modules'][$this->pluginName]['controllers'][$controllerName] = array(
                    'actions' => GeneralUtility::trimExplode(',', $actions)
                );
            }
            $this->bootstrap = new Bootstrap();
        } else {
            //set the default allowed controller action combinations
            foreach ($this->controllerActions as $controllerName => $actions) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['modules'][$this->pluginName]['controllers'][$this->controllerClass] = array(
                    'className' => $this->controllerClass,
                    'alias' => $this->controllerAlias,
                    'actions' => GeneralUtility::trimExplode(',', $actions),
                );
            }
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $this->bootstrap = $this->objectManager->get(Bootstrap::class);
        }
    }

    /**
     * Provides a selection of legislators for the plugin
     *
     * @param array $params
     */
    public function readLegislator(array &$params)
    {
        $apiKey = '';
        //read and provide flexform
        if (isset($params['row']['pi_flexform']) && !empty($params['row']['pi_flexform'])) {
            $data = GeneralUtility::xml2array($params['row']['pi_flexform']);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey', 'sDEF');
        } elseif (isset($params['row']['uid']) && isset($params['table']) && is_numeric($params['row']['uid'])) {
            $pi_flexform = $this->getPiFlexformFromTable($params['table'], $params['row']['uid']);
            $data = GeneralUtility::xml2array($pi_flexform);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey', 'sDEF');
        }
        //test for double call
        $post = GeneralUtility::_GP('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName));
        //first call
        if (!isset($post['jsonLegislator']) || empty($post['jsonLegislator']) || $params['config']['action'] != $post['action']) {
            unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['apiKey'] = $request['settings']['apiKey'] = $apiKey;
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $params['config']['controller'];
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $params['config']['action'];
            //For TYPO3 9.5 put query parameters in the backend
            if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
                $queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
                $queryParams['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)] = $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)];
                $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withQueryParams($queryParams);
            }
            $this->configuration['controller'] = $params['config']['controller'];
            $this->configuration['action'] = $params['config']['action'];
            $this->configuration = array_merge($this->configuration, $request);
            //start of Extbase bootstrap program
            $json = $this->bootstrap->run('', $this->configuration);

            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['jsonLegislator'] = addslashes($json);
            $items = json_decode($json, true);
            if (!empty($items) && is_array($items)) {
                foreach ($items['legislator'] as $item) {
                    $params['items'][] = array($item['name'], $item['id']);
                }
            }
            //second call
        } else {
            if (isset($post['jsonLegislator']) && !empty($post['jsonLegislator'])) {
                $json = stripslashes($post['jsonLegislator']);
                $items = json_decode($json, true);
                if (!empty($items) && is_array($items)) {
                    foreach ($items['legislator'] as $item) {
                        $params['items'][] = array($item['name'], $item['id']);
                    }
                }
            }
            if (isset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)])) {
                unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
            }
        }
    }

    /**
     * Reads the structure of the legal norms in connection with the legislator.
     *
     * @param array $params
     */
    public function readStructure(array &$params)
    {
        $apiKey = $legislatorId = '';
        //read and provide flexform
        if (isset($params['row']['pi_flexform']) && !empty($params['row']['pi_flexform'])) {
            $data = GeneralUtility::xml2array($params['row']['pi_flexform']);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey', 'sDEF');
            $legislatorId = $this->pi_getFFvalue($data, 'settings.legislatorId', 'sDEF');
        } elseif (isset($params['row']['uid']) && isset($params['table']) && is_numeric($params['row']['uid'])) {
            $pi_flexform = $this->getPiFlexformFromTable($params['table'], $params['row']['uid']);
            $data = GeneralUtility::xml2array($pi_flexform);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey', 'sDEF');
            $legislatorId = $this->pi_getFFvalue($data, 'settings.legislatorId', 'sDEF');
        }
        //test for double call
        $post = GeneralUtility::_GP('tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName));
        //first call
        if (!isset($post['jsonStructure']) || empty($post['jsonStructure']) || $params['config']['action'] != $post['action']) {
            unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['apiKey'] = $request['settings']['apiKey'] = $apiKey;
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['legislatorId'] = $request['settings']['legislatorId'] = $legislatorId;
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $params['config']['controller'];
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $params['config']['action'];
            //For TYPO3 9.5 put query parameters in the backend
            if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
                $queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
                $queryParams['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)] = $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)];
                $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withQueryParams($queryParams);
            }
            $this->configuration['controller'] = $params['config']['controller'];
            $this->configuration['action'] = $params['config']['action'];
            $this->configuration = array_merge($this->configuration, $request);
            //start of Extbase bootstrap program
            $json = $this->bootstrap->run('', $this->configuration);

            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['jsonStructure'] = addslashes($json);
            $items = json_decode($json, true);
            if (!empty($items) && is_array($items)) {
                foreach ($items['structure'] as $item) {
                    $params['items'][] = array($item['name'], $item['id']);
                }
            }
            //second call
        } else {
            if (isset($post['jsonStructure']) && !empty($post['jsonStructure'])) {
                $json = stripslashes($post['jsonStructure']);
                $items = json_decode($json, true);
                if (!empty($items) && is_array($items)) {
                    foreach ($items['structure'] as $item) {
                        $params['items'][] = array($item['name'], $item['id']);
                    }
                }
            }
            if (isset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)])) {
                unset($_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]);
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like
     *                                 "test/el/2/test/el/field_templateObject" where each part will dig a level deeper
     *                                 in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     *
     * @return string|NULL The content.
     */
    public function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF')
    {
        $sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensiona array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array
     *                             will have the value returned pointed to by these keys. All integer keys will not
     *                             take their integer counterparts, but rather traverse the current position in the
     *                             array an return element number X (whether this is right behavior is not settled
     *                             yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     *
     * @return mixed The value, typ. string.
     * @access private
     * @see    pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } else {
                $tempArr = $tempArr[$v];
            }
        }
        return $tempArr[$value];
    }

    /**
     * Read the Flex form from the database
     *
     * @param string $table
     * @param integer $uid
     *
     * @return string $pi_flexform
     */
    protected function getPiFlexformFromTable($table, $uid)
    {
        $pi_flexform = '';
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $res = $queryBuilder->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
            ->groupBy('uid')
            ->execute();
        $row = $res->fetch();

        if (isset($row['pi_flexform']) && !empty($row['pi_flexform'])) {
            $pi_flexform = $row['pi_flexform'];
        }
        return $pi_flexform;
    }
}