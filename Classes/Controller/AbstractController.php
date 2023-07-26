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

namespace Nwsnet\NwsMunicipalStatutes\Controller;

use Doctrine\DBAL\DBALException;
use Exception;
use Nwsnet\NwsMunicipalStatutes\Session\UserSession;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;

use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Abstract controllers that provide the error messages
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
abstract class AbstractController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const MAX_ALIAS_LENGTH = 100;

    /**
     * Extension Name
     *
     * @var string
     */
    protected $extensionName = 'NwsMunicipalStatutes';

    /**
     * $_EXTKEY
     *
     * @var string $extKey
     */
    protected $extKey = 'nws_municipal_statutes';

    /**
     * UserSession
     *
     * @var UserSession
     */
    protected $userSession;

    /**
     * unknownErrorMessage
     *
     * @var string
     */
    protected $unknownErrorMessage = 'An unknown error occurred. We´re about as soon as possible to resolve this faith.';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * ApiCall set data
     *
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param UserSession $userSession
     */
    public function injectUserSession(UserSession $userSession)
    {
        $this->userSession = $userSession;
    }

    /**
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManagerInterface(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Calls the specified method and passes the arguments
     *
     * @param RequestInterface|null $request
     * @return ResponseInterface
     * @throws ImmediateResponseException
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     * @throws PageNotFoundException
     */
    public function callActionMethod(RequestInterface $request = null): ResponseInterface
    {
        try {
            if (empty($request)) {
                parent::callActionMethod();
                $response = new Response();
            } else {
                $response = parent::callActionMethod($request);
            }
        } catch (InvalidRequestMethodException $e) {
            $this->logger->debug($e->getMessage(), [$this->request->getControllerExtensionKey() => 2]);
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $GLOBALS['TYPO3_REQUEST'],
                $e->getMessage(),
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
            throw new ImmediateResponseException($response);
        }  catch (Exception $e) {
            $this->logger->debug($e->getMessage(), [$this->request->getControllerExtensionKey() => 3]);
            throw $e;
        }

        return $response;
    }

    /**
     * Provide the error message for output within the page
     *
     * @param Exception $e
     * @return ResponseInterface
     */
    protected function handleError(Exception $e)
    {
        $controllerContext = $this->buildControllerContext();
        $controllerContext->getRequest()->setControllerName('Exception');
        $controllerContext->getRequest()->setControllerActionName('error');
        $this->view->setControllerContext($controllerContext);
        $content = $this->view->assign('exception', $e)->render('error');
        if (method_exists($this, 'htmlResponse')) {
            return $this->htmlResponse($content);
        } else {
            $this->response->appendContent($content);

            return $this->response;
        }
    }
    /**
     * Provide the libraries for OpenStreet map
     *
     * @return void
     */
    protected function includeLeafletAssets()
    {
        $this->pageRenderer->addCssFile('//cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.0-beta.2.rc.2/leaflet.css',
            'stylesheet', 'all', '', false, false, '', true);
        $this->pageRenderer->addJsLibrary('leaflet',
            '//cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.0-beta.2.rc.2/leaflet.js', 'text/javascript', false, false,
            '', true);
    }

    /**
     * Gets the full TypoScript for the extension without it being overwritten with the "flexform"
     *
     * @return array $settings
     */
    protected function getTypoScript()
    {
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

        return $typoScriptService->convertTypoScriptArrayToPlainArray($configuration['plugin.']['tx_nwsmunicipalstatutes.']['settings.']);
    }

    /**
     * Overrides the preset TypoScript values in the settings
     *
     * @param array $overrideKeys
     * @param array $params
     * @param array $localSettings
     *
     * @return array
     */
    protected function overrideParameterFromTypoScript(array $overrideKeys, array $params, array $localSettings)
    {
        if (is_array($params) && is_array($localSettings)) {
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        if (!is_array($subValue)) {
                            if (key_exists($subKey, $overrideKeys)) {
                                if (empty($subValue) && isset($localSettings[$key][$subKey]) && !empty($localSettings[$key][$subKey])) {
                                    $params[$key][$subKey] = $localSettings[$key][$subKey];
                                }
                            }
                        }
                    }
                } elseif (key_exists($key, $overrideKeys)) {
                    if (empty($value) && isset($localSettings[$key]) && !empty($localSettings[$key])) {
                        $params[$key] = $localSettings[$key];
                    }
                }
            }
        }
        return $params;
    }

    /**
     * Create a query from arrays
     *
     * @param $array
     * @param bool $qs
     * @return string
     */
    protected function httpBuildQuery($array, $qs = false)
    {
        $parts = array();
        if ($qs) {
            $parts[] = $qs;
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_int($key2)) {
                        $parts[] = http_build_query(array($key => $value2));
                    } else {
                        $parts[] = http_build_query(array($key . '[' . $key2 . ']' => $value2));
                    }
                }
            } else {
                $parts[] = http_build_query(array($key => $value));
            }
        }
        return join('&', $parts);
    }

    /**
     * Converts a given string to a string that can be used as a URL segment.
     * The result is not url-encoded.
     *
     * @param string $processedTitle
     * @param string $spaceCharacter
     * @param bool $strToLower
     * @return string
     */
    protected function convertToSafeString($processedTitle, $spaceCharacter = '-', $strToLower = true)
    {
        /** @var CharsetConverter $csConvertor */
        $csConvertor = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
        if ($strToLower) {
            $processedTitle = mb_strtolower($processedTitle, 'UTF-8');
        }
        $processedTitle = strip_tags($processedTitle);
        $processedTitle = preg_replace('/[ \t\x{00A0}\-+_]+/u', $spaceCharacter, $processedTitle);
        $processedTitle = $csConvertor->specCharsToASCII('utf-8', $processedTitle);
        $processedTitle = preg_replace('/[^\p{L}0-9' . preg_quote($spaceCharacter) . ']/u', '', $processedTitle);
        $processedTitle = preg_replace('/' . preg_quote($spaceCharacter) . '{2,}/', $spaceCharacter, $processedTitle);
        $processedTitle = trim($processedTitle, $spaceCharacter);

        if ($strToLower) {
            $processedTitle = strtolower($processedTitle);
        }

        return $processedTitle;
    }

    /**
     * Read the Flex form from the database
     *
     * @param string $table
     * @param integer $uid
     *
     * @return array $row
     * @throws DBALException
     */
    protected function getContentDataArray($table, $uid)
    {
        if (isset($GLOBALS['TYPO3_DB'])) {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . $uid);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        } else {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $res = $queryBuilder->select('*')
                ->from('tt_content')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
                ->groupBy('uid')
                ->execute();
            $row = $res->fetch();
        }
        return $row;
    }
}