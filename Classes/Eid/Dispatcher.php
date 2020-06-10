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

namespace Nwsnet\NwsMunicipalStatutes\Eid;

use Nwsnet\NwsMunicipalStatutes\Http\RequestHandler;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * Class Dispatcher, provide Eid calls for controllers and action
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */

/**
 * = Examples =
 *
 * <f:link.action action="month" additionalParams="{eID:'nwsMunicipalStatutesDispatcher'}" arguments="{test:3}" >
 *        Linkname
 * </f:link.action>
 *
 * OR
 *
 * <code title="URI to the show-action of the current controller">
 * <nws:uri.ajaxAction action="month" additionalParams="{eID:'nwsMunicipalStatutesDispatcher'}" arguments="{test:3}">
 *        Link
 * </nws:uri.ajaxAction>
 * </code>
 * <output>
 *        index.php?id=123&tx_myextension_plugin[context]=tt_content:123&tx_myextension_plugin[action]=month&tx_myextension_plugin[controller]=Standard&cHash=xyz
 *        (depending on the current page and your TS configuration)
 * </output>
 */
class Dispatcher
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
    private $pluginName = 'Pi1';

    /**
     * Default Controller
     *
     * @var string
     */
    private $defaultController = 'LocalLaw';

    /**
     * Default action
     *
     * @var string
     */
    private $defaultAction = 'list';

    /**
     * Patter for transmitted get parameter
     *
     * @var string
     */
    private $arrayPattern = 'tx_nwsmunicipalstatutes_pi1';

    /**
     * Bootstrap Configuration
     *
     * @var array
     */
    private $configuration;

    /**
     * @var string
     */
    protected $requestHandler = RequestHandler::class;

    /**
     * @var array
     */
    private $middlewares = array(
        'typo3/cms-frontend/prepare-tsfe-rendering' => 'TYPO3\\CMS\\Frontend\\Middleware\\PrepareTypoScriptFrontendRendering',
        'typo3/cms-frontend/page-argument-validator' => 'TYPO3\\CMS\\Frontend\\Middleware\\PageArgumentValidator',
        'typo3/cms-frontend/maintenance-mode' => 'TYPO3\\CMS\\Frontend\\Middleware\\MaintenanceMode',
        'typo3/cms-frontend/page-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\PageResolver',
        'typo3/cms-redirects/redirecthandler' => 'TYPO3\\CMS\\Redirects\\Http\\Middleware\\RedirectHandler',
        'typo3/cms-frontend/static-route-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\StaticRouteResolver',
        'typo3/cms-frontend/base-redirect-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\SiteBaseRedirectResolver',
        'typo3/cms-frontend/site' => 'TYPO3\\CMS\\Frontend\\Middleware\\SiteResolver',
        'typo3/cms-frontend/authentication' => 'TYPO3\\CMS\\Frontend\\Middleware\\FrontendUserAuthenticator',
        'typo3/cms-frontend/tsfe' => 'TYPO3\\CMS\\Frontend\\Middleware\\TypoScriptFrontendInitialization',
    );


    /**
     * Distributor for bootstrap calls with controller and action
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws ServiceUnavailableException
     * @throws ImmediateResponseException
     */
    public function processRequest(ServerRequestInterface $request)
    {
        $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($versionAsInt > 8999999) {
            //Initialization of the response via middleware request
            $response = $this->handle($request);
        } else {
            $pageId = GeneralUtility::_GET('id') ?: 1;

            /**
             * Initialize fe_user (Session and cookie data read out)
             */
            $feUserObj = EidUtility::initFeUser();

            /** @var TypoScriptFrontendController $typoScriptFrontendController */
            $typoScriptFrontendController = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                $pageId, // page ID
                0 // pageType.
            );
            $GLOBALS['TSFE'] = $typoScriptFrontendController;
            $typoScriptFrontendController->checkAlternativeIdMethods();
            $typoScriptFrontendController->connectToDB();
            $typoScriptFrontendController->fe_user = $feUserObj;
            $typoScriptFrontendController->determineId();
            $typoScriptFrontendController->initTemplate();
            $typoScriptFrontendController->getConfigArray();
            $typoScriptFrontendController->settingLanguage();
            $typoScriptFrontendController->settingLocale();

            // Extract parameters based on the extension patter
            $params = GeneralUtility::_GP($this->arrayPattern);
            $cHash = GeneralUtility::_GP('cHash');
            if (!empty($cHash)) {
                $typoScriptFrontendController->cHash = $cHash;
            }
            //Read ContextRecord for Flexform
            if (isset($params['context']) && strpos($params['context'], ':') !== false) {
                list($table, $uid) = explode(':', $params['context']);
            }

            //set the configuration
            $this->configuration = array(
                'vendorName' => $this->vendorName,
                'extensionName' => $this->extensionName,
                'pluginName' => $this->pluginName,

            );
            $this->configuration['controller'] = isset($params['controller']) ? $params['controller'] : $this->defaultController;
            $this->configuration['action'] = isset($params['action']) ? $params['action'] : $this->defaultAction;
            $this->configuration['mvc'] = array(
                'requestHandlers' => array('TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler' => 'TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler'),
            );
            // Set the POST Request
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['controller'] = $this->configuration['controller'];
            $_POST['tx_' . strtolower($this->extensionName) . '_' . strtolower($this->pluginName)]['action'] = $this->configuration['action'];

            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
            $pluginConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScriptFrontendController->tmpl->setup['plugin.']['tx_nwsmunicipalstatutes.']);

            $this->configuration['settings'] = $pluginConfiguration['settings'];
            $this->configuration['persistence'] = array('storagePid' => $pluginConfiguration['persistence']['storagePid']);
            //TYPO3 >= 8.7  must be switched off the cHash validate
            if (empty($cHash)) {
                $this->configuration['features']['requireCHashArgumentForActionArguments'] = 0;
            }


            /**
             * Initialize Extbase bootstrap
             */
            $bootstrap = new Bootstrap();
            $bootstrap->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
                $typoScriptFrontendController);

            //initalize the data us the content element
            if (isset($table) && isset($uid)) {
                $data = $this->getContentDataArray($table, $uid);
                if (is_array($data)) {
                    $bootstrap->cObj->start($data, 'tt_content');
                }
            }
            //output
            $typoScriptFrontendController->content = $bootstrap->run('', $this->configuration);
            $isOutputting = !empty($typoScriptFrontendController->content) ? true : false;
            // Create a Response object when sending content
            $response = new Response();

            // Store session data for fe_users
            $typoScriptFrontendController->fe_user->storeSessionData();

            $response->getBody()->write($typoScriptFrontendController->content);

            return $isOutputting ? $response : new NullResponse();
        }
        return $response;
    }

    /**
     * Read the Flex form from the database
     *
     * @param string $table
     * @param integer $uid
     *
     * @return array $row
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

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestHandler $requestHandler */
        $requestHandler = GeneralUtility::makeInstance($this->requestHandler);
        $dispatcher = $this->createMiddlewareDispatcher($requestHandler);

        return $dispatcher->handle($request);
    }

    /**
     * @param RequestHandlerInterface $requestHandler
     *
     * @return MiddlewareDispatcher
     */
    protected function createMiddlewareDispatcher(RequestHandlerInterface $requestHandler)
    {
        $middlewares = $this->middlewares;

        return new MiddlewareDispatcher($requestHandler, $middlewares);
    }
}