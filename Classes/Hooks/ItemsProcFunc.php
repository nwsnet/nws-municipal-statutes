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

use Doctrine\DBAL\Exception;
use Nwsnet\NwsMunicipalStatutes\Exception\UnsupportedRequestTypeException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
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
    private string $vendorName = 'Nwsnet';

    /**
     * extensionName
     *
     * @var string
     */
    private string $extensionName = 'NwsMunicipalStatutes';

    /**
     * pluginName
     *
     * @var string
     */
    private string $pluginName = 'Items1';

    /**
     * configuration
     *
     * @var array
     */
    private array $configuration;

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
    private array $controllerActions = array(  // Allowed controller action combinations
        'ItemsProcFunc' => 'readLegislator,readStructure',
    );

    /**
     * TYPO3 10.x controller class
     * @var string
     */
    private string $controllerClass = "Nwsnet\NwsMunicipalStatutes\Controller\ItemsProcFuncController";

    /**
     * TYPO3 10.x controller alias
     * @var string
     */
    private string $controllerAlias = "ItemsProcFunc";

    private $backendRequest = null;

    /**
     * Initialize Extbase
     *
     * @throws \Exception
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

        //set the default allowed controller action combinations
        foreach ($this->controllerActions as $actions) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['modules'][$this->pluginName]['controllers'][$this->controllerClass] = array(
                'className' => $this->controllerClass,
                'alias' => $this->controllerAlias,
                'actions' => GeneralUtility::trimExplode(',', $actions),
            );
        }
        if ($this->getTypo3Version() < 12000000) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $this->bootstrap = $objectManager->get(Bootstrap::class);
        } else {
            $this->backendRequest = clone $GLOBALS['TYPO3_REQUEST'];
            if (isset($GLOBALS['BE_USER']->user['lang'])) {
                $lang = $GLOBALS['BE_USER']->user['lang'];
                $site = $GLOBALS['TYPO3_REQUEST']->getAttribute('site');
                $backendLanguage = current($site->getLanguages());
                $configuration = $backendLanguage->toArray();
                $configuration['typo3Language'] = $lang;
                $language = new SiteLanguage(0, $lang, new Uri('/'), $configuration);
                $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $language);
            }
            $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute(
                'applicationType',
                SystemEnvironmentBuilder::REQUESTTYPE_FE
            );
            /** @var FrontendTypoScript $frontendTypoScript */
            $frontendTypoScript = GeneralUtility::makeInstance(
                FrontendTypoScript::class,
                GeneralUtility::makeInstance(RootNode::class),
                [],
                [],
                []
            );
            $frontendTypoScript->setSetupArray([]);
            $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute(
                'frontend.typoscript',
                $frontendTypoScript
            );
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['plugins'][$this->pluginName]['controllers'][$this->controllerClass] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$this->extensionName]['modules'][$this->pluginName]['controllers'][$this->controllerClass];
            $this->bootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        }
    }

    /**
     * Executing the call and determining the data
     *
     * @param array $params
     * @throws Exception
     */
    public function execute(array &$params)
    {
        $apiKey = '';
        $request = [];
        $legislatorId = 0;
        $itemName = 'json'.ucfirst($this->getActionName($params));
        if (!empty($params['config']['filter'] ?? '')) {
            $itemName .= ucfirst($params['config']['filter']);
        }
        //read and provide flexform
        if (!empty($params['row']['pi_flexform'] ?? null)) {
            $data = GeneralUtility::xml2array($params['row']['pi_flexform']);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey');
            $legislatorId = $this->pi_getFFvalue($data, 'settings.legislatorId');
        } elseif (isset($params['row']['uid']) && isset($params['table']) && is_numeric($params['row']['uid'])) {
            $pi_flexform = $this->getPiFlexformFromTable($params['table'], $params['row']['uid']);
            $data = GeneralUtility::xml2array($pi_flexform);
            $apiKey = $this->pi_getFFvalue($data, 'settings.apiKey');
            $legislatorId = $this->pi_getFFvalue($data, 'settings.legislatorId');
        }
        //test for double call
        $pattern = 'tx_'.strtolower($this->extensionName).'_'.strtolower($this->pluginName);
        if (method_exists(GeneralUtility::class, '_GP')) {
            $post = GeneralUtility::_GP($pattern);
        } else {
            $post = $GLOBALS['TYPO3_REQUEST']->getParsedBody()[$pattern]
                ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()[$pattern]
                ?? null;
        }
        //first call
        if (empty($post[$itemName] ?? '') || $params['config']['action'] !== $post['action']) {
            unset($_POST[$pattern]);
            $_POST[$pattern]['apiKey'] = $request['settings']['apiKey'] = $apiKey;
            $_POST[$pattern]['controller'] = $params['config']['controller'];
            $_POST[$pattern]['action'] = $params['config']['action'];
            $_POST[$pattern]['legislatorId'] = $request['settings']['legislatorId'] = $legislatorId;
            if (!empty($params['config']['filter'] ?? null)) {
                $_POST[$pattern]['filter'] = $params['config']['filter'];
            }
            //For TYPO3 9.5 put query parameters in the backend
            if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
                $queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
                $queryParams['tx_'.strtolower($this->extensionName).'_'.strtolower(
                    $this->pluginName
                )] = $_POST['tx_'.strtolower($this->extensionName).'_'.strtolower($this->pluginName)];
                $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withQueryParams($queryParams);
            }
            $this->configuration['controller'] = $params['config']['controller'];
            $this->configuration['action'] = $params['config']['action'];
            $this->configuration['switchableControllerActions'][$this->configuration['controller']][] = $this->configuration['action'];
            $this->configuration = array_merge($this->configuration, $request);
            //start of Extbase bootstrap program
            try {
                if ($this->getTypo3Version() < 12000000) {
                    $json = $this->bootstrap->run('', $this->configuration);
                } else {
                    $json = $this->bootstrap->run('', $this->configuration, $GLOBALS['TYPO3_REQUEST']);
                }
            } catch (UnsupportedRequestTypeException $e) {
                $json = json_encode(['items' => [0 => ['name' => $e->getMessage(), 'id' => 0]]]);
            }

            $_POST[$pattern][$itemName] = addslashes($json);
            $items = json_decode($json, true);
            if (!empty($items) && is_array($items)) {
                foreach ($items['items'] as $item) {
                    $params['items'][] = array($item['name'], $item['id']);
                }
            }
            //second call
        } else {
            if (!empty($post[$itemName] ?? null)) {
                $json = stripslashes($post[$itemName]);
                $items = json_decode($json, true);
                if (!empty($items) && is_array($items)) {
                    foreach ($items['items'] as $item) {
                        $params['items'][] = array($item['name'], $item['id']);
                    }
                }
            }
            if (isset($_POST[$pattern])) {
                unset($_POST[$pattern]);
            }
        }
        if ($this->getTypo3Version() > 12000000) {
            $GLOBALS['TYPO3_REQUEST'] = $this->backendRequest;
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array|string $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like
     *                                 "test/el/2/test/el/field_templateObject" where each part will dig a level deeper
     *                                 in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     *
     * @return string|NULL The content.
     */
    public function pi_getFFvalue(
        $T3FlexForm_array,
        string $fieldName,
        string $sheet = 'sDEF',
        string $lang = 'lDEF',
        string $value = 'vDEF'
    ): ?string {
        $sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }

        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array
     *                             will have the value returned pointed to by these keys. All integer keys will not
     *                             take their integer counterparts, but rather traverse the current position in the
     *                             array a return element number X (whether this is right behavior is not settled
     *                             yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     *
     * @return mixed The value, typ. string.
     * @access private
     * @see    pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray(array $sheetArray, array $fieldNameArr, string $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $v) {
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
                $tempArr = $tempArr[$v] ?? [$value => null];
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
     * @return string
     * @throws Exception
     */
    protected function getPiFlexformFromTable(string $table, int $uid): string
    {
        $pi_flexform = '';

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $res = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
        if (method_exists($res, 'execute')) {
            $row = $res->execute()->fetch();
        } else {
            $row = current($res->executeQuery()->fetchAllAssociative());
        }


        if (!empty($row['pi_flexform'] ?? '')) {
            $pi_flexform = $row['pi_flexform'];
        }

        return $pi_flexform;
    }

    /**
     * @param array $param
     * @return string
     */
    private function getActionName(array $param): string
    {
        $actionName = '';
        if (!empty($param['config']['action'] ?? null)) {
            $actionName = $param['config']['action'];
        }

        return $actionName;
    }

    /**
     * Retrieves the TYPO3 version as an integer.
     *
     * @return int The TYPO3 version number converted to an integer.
     */
    private function getTypo3Version(): int
    {
        if (defined('TYPO3_version')) {
            return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        } else {
            $version = VersionNumberUtility::getNumericTypo3Version();

            return VersionNumberUtility::convertVersionNumberToInteger($version);
        }
    }

}