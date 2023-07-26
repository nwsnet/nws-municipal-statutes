<?php

declare(strict_types=1);
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

namespace Nwsnet\NwsMunicipalStatutes\Http;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This is the main entry point of the TypoScript driven standard front-end.
 *
 * "handle()" is called when all PSR-15 middlewares have been set up the PSR-7 ServerRequest object and the following
 * things have been evaluated
 * - correct page ID, page type (typeNum), rootline, MP etc.
 * - info if is cached content already available
 * - proper language
 * - proper TypoScript which should be processed.
 *
 * Then, this class is able to render the actual HTTP body part built via TypoScript. Here this is split into two parts:
 * - Everything included in <body>, done via page.10, page.20 etc.
 * - Everything around.
 *
 * If the content has been built together within the cache (cache_pages), it is fetched directly, and
 * any so-called "uncached" content is generated again.
 *
 * Some further hooks allow to post-processing the content.
 *
 * Then the right HTTP response headers are compiled together and sent as well.
 */
class RequestHandler implements PsrRequestHandlerInterface
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
     * Default pluginName
     *
     * @var string
     */
    private $defaultPluginName = 'Pi1';

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
     * Default Patter for transmitted get parameter
     *
     * @var string
     */
    private $defaultArrayPattern = 'tx_nwsmunicipalstatutes_pi1';

    /**
     * Pattern list for transmitted get parameter
     *
     * @var array
     */
    private $arrayPattern = [
        'tx_nwsmunicipalstatutes_pi1',
    ];


    /**
     * Handles a frontend request
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Puts parameters that have been added or removed from the global _GET or _POST arrays
     * into the given request (however, the PSR-7 request information takes precedence).
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    protected function addModifiedGlobalsToIncomingRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $originalGetParameters = $request->getAttribute('_originalGetParameters');
        if ($originalGetParameters !== null && !empty($_GET) && $_GET !== $originalGetParameters) {
            // Find out what has been changed.
            $modifiedGetParameters = ArrayUtility::arrayDiffAssocRecursive($_GET ?? [], $originalGetParameters);
            if (!empty($modifiedGetParameters)) {
                $queryParams = array_replace_recursive($modifiedGetParameters, $request->getQueryParams());
                $request = $request->withQueryParams($queryParams);
                $GLOBALS['TYPO3_REQUEST'] = $request;
            }
        }
        // do same for $_POST if the request is a POST request
        $originalPostParameters = $request->getAttribute('_originalPostParameters');
        if ($request->getMethod() === 'POST' && $originalPostParameters !== null && !empty($_POST) && $_POST !== $originalPostParameters) {
            // Find out what has been changed
            $modifiedPostParameters = ArrayUtility::arrayDiffAssocRecursive($_POST ?? [], $originalPostParameters);
            if (!empty($modifiedPostParameters)) {
                $parsedBody = array_replace_recursive($modifiedPostParameters, $request->getParsedBody());
                $request = $request->withParsedBody($parsedBody);
                $GLOBALS['TYPO3_REQUEST'] = $request;
            }
        }
        return $request;
    }

    /**
     * Sets the global GET and POST to the values, so if people access $_GET and $_POST
     * Within hooks starting NOW (e.g. cObject), they get the "enriched" data from query params.
     *
     * This needs to be run after the request object has been enriched with modified GET/POST variables.
     *
     * @param ServerRequestInterface $request
     *
     * @internal this safety net will be removed in TYPO3 v10.0.
     */
    protected function resetGlobalsToCurrentRequest(ServerRequestInterface $request)
    {
        if ($request->getQueryParams() !== $_GET) {
            $queryParams = $request->getQueryParams();
            $_GET = $queryParams;
            $GLOBALS['HTTP_GET_VARS'] = $_GET;
        }
        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && $parsedBody !== $_POST) {
                $_POST = $parsedBody;
                $GLOBALS['HTTP_POST_VARS'] = $_POST;
            }
        }
    }

    /**
     * Handles a frontend request, after finishing running middlewares
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|null
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var TypoScriptFrontendController $controller */
        $typoScriptFrontendController = $GLOBALS['TSFE'];

        // safety net, will be removed in TYPO3 v10.0. Aligns $_GET/$_POST to the incoming request.
        $request = $this->addModifiedGlobalsToIncomingRequest($request);
        $this->resetGlobalsToCurrentRequest($request);


        $params = GeneralUtility::_GPmerged($this->getArrayPattern($request));
        $cHash = GeneralUtility::_GET('cHash');
        if (!empty($cHash)) {
            $typoScriptFrontendController->cHash = $cHash;
        }
        //Read ContextRecord for Flexform
        if (isset($params['context']) && strpos($params['context'], '|') !== false) {
            list($table, $uid) = explode('|', $params['context']);
        }

        //set the configuration
        $configuration = array(
            'vendorName' => $this->vendorName,
            'extensionName' => $this->extensionName,
            'pluginName' => $this->getPluginName($request),

        );
        $configuration['controller'] = isset($params['controller']) ? $params['controller'] : $this->defaultController;
        $configuration['action'] = isset($params['action']) ? $params['action'] : $this->defaultAction;

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TypoScriptService');

        $pluginConfiguration['settings'] = [];
        if (isset($typoScriptFrontendController->tmpl->setup['plugin.']['tx_nwsmunicipalstatutes.'])) {
            $pluginConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray(
                $typoScriptFrontendController->tmpl->setup['plugin.']['tx_nwsmunicipalstatutes.']
            );
        }

        $configuration['settings'] = $pluginConfiguration['settings'];
        $configuration['persistence'] = array('storagePid' => $pluginConfiguration['persistence']['storagePid']);
        //TYPO3 >= 8.7  must be switched off the cHash validate
        if (empty($cHash)) {
            $configuration['features']['requireCHashArgumentForActionArguments'] = 0;
        }


        /**
         * Initialize Extbase bootstrap
         */

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var Bootstrap $bootstrap */
        $bootstrap = $objectManager->get(Bootstrap::class);
        if (method_exists($bootstrap, 'setContentObjectRenderer')) {
            /** @var ContentObjectRenderer $contentObjectRenderer */
            $contentObjectRenderer = GeneralUtility::makeInstance(
                'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
                $typoScriptFrontendController
            );
            //initalize the data us the content element
            if (isset($table) && isset($uid)) {
                $data = $this->getContentDataArray($table, intval($uid));
                if (is_array($data) && !empty($data)) {
                    $contentObjectRenderer->start($data, 'tt_content');
                }
            }

            $bootstrap->setContentObjectRenderer($contentObjectRenderer);
        } else {
            $bootstrap->cObj = GeneralUtility::makeInstance(
                'TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer',
                $typoScriptFrontendController
            );
        //initalize the data us the content element
        if (isset($table) && isset($uid)) {
                $data = $this->getContentDataArray($table, intval($uid));
                if (is_array($data) && !empty($data)) {
                $bootstrap->cObj->start($data, 'tt_content');
            }
        }
        }

        //output
        $typoScriptFrontendController->content = $bootstrap->run('', $configuration);
        $isOutputting = !empty($typoScriptFrontendController->content);
        // Create a Response object when sending content
        $response = new Response();

        // Store session data for fe_users
        $typoScriptFrontendController->fe_user->storeSessionData();

        $response->getBody()->write($typoScriptFrontendController->content);
        if(method_exists($typoScriptFrontendController,'applyHttpHeadersToResponse')){
            $response = $typoScriptFrontendController->applyHttpHeadersToResponse($response);
        }

        return $isOutputting ? $response : new NullResponse();
    }

    /**
     * Read the Flex form from the database
     *
     * @param string $table
     * @param integer $uid
     *
     * @return array $row
     */
    protected function getContentDataArray(string $table, int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $res = $queryBuilder->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
            ->groupBy('uid')
            ->execute();
        $result = $res->fetch();

        return !empty($result) ? $result : [];
    }

    /**
     * Determines the array pattern that was used for the get transmission
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getArrayPattern(ServerRequestInterface $request): string
    {
        $result = $this->defaultArrayPattern;
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            foreach ($this->arrayPattern as $patter) {
                if (isset($queryParams[$patter]) && !empty($queryParams[$patter])) {
                    $result = $patter;
                }
            }
        }

        return $result;
    }

    /**
     * Determines the plugin name from the request data
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getPluginName(ServerRequestInterface $request): string
    {
        $result = $this->defaultPluginName;
        $plugin = GeneralUtility::trimExplode('_', $this->getArrayPattern($request));
        if (!empty($plugin) && is_array($plugin)) {
            $result = ucfirst(end($plugin));
        }

        return $result;
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
    }
}
