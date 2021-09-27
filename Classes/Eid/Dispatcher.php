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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

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
     * @var string
     */
    protected $requestHandler = RequestHandler::class;

    /**
     * @var array
     */
    private $middlewares9 = array(
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
     * @var array
     */
    private $middlewares10 = array(
        'typo3/cms-frontend/prepare-tsfe-rendering' => 'TYPO3\\CMS\\Frontend\\Middleware\\PrepareTypoScriptFrontendRendering',
        'typo3/cms-frontend/tsfe' => 'TYPO3\\CMS\\Frontend\\Middleware\\TypoScriptFrontendInitialization',
        'typo3/cms-frontend/page-argument-validator' => 'TYPO3\\CMS\\Frontend\\Middleware\\PageArgumentValidator',
        'typo3/cms-frontend/page-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\PageResolver',
        'typo3/cms-redirects/redirecthandler' => 'TYPO3\\CMS\\Redirects\\Http\\Middleware\\RedirectHandler',
        'typo3/cms-frontend/static-route-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\StaticRouteResolver',
        'typo3/cms-frontend/base-redirect-resolver' => 'TYPO3\\CMS\\Frontend\\Middleware\\SiteBaseRedirectResolver',
        'typo3/cms-frontend/authentication' => 'TYPO3\\CMS\\Frontend\\Middleware\\FrontendUserAuthenticator',
        'typo3/cms-frontend/site' => 'TYPO3\\CMS\\Frontend\\Middleware\\SiteResolver',
        'typo3/cms-frontend/maintenance-mode' => 'TYPO3\\CMS\\Frontend\\Middleware\\MaintenanceMode',
    );

    /**
     * Distributor for bootstrap calls with controller and action
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request)
    {
        //Initialization of the response via middleware request
        return $this->handle($request);
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
        $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        if ($versionAsInt < 9999999) {
            $middlewares = $this->middlewares9;
        } else {
            $middlewares = $this->middlewares10;
        }

        return new MiddlewareDispatcher($requestHandler, $middlewares);
    }
}