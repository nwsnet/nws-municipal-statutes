<?php
declare(strict_types=1);

namespace Nwsnet\NwsMunicipalStatutes\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
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
class RequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
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
		$originalGetParameters = $request->getAttribute('_originalGetParameters', null);
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
		$originalPostParameters = $request->getAttribute('_originalPostParameters', null);
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
		$configuration = array(
			'vendorName' => $this->vendorName,
			'extensionName' => $this->extensionName,
			'pluginName' => $this->pluginName,

		);
		$configuration['controller'] = isset($params['controller']) ? $params['controller'] : $this->defaultController;
		$configuration['action'] = isset($params['action']) ? $params['action'] : $this->defaultAction;

		/** @var TypoScriptService $typoScriptService */
		$typoScriptService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
		$pluginConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScriptFrontendController->tmpl->setup['plugin.']['tx_nwsmunicipalstatutes.']);

		$configuration['settings'] = $pluginConfiguration['settings'];
		$configuration['persistence'] = array('storagePid' => $pluginConfiguration['persistence']['storagePid']);
		//TYPO3 >= 8.7  must be switched off the cHash validate
		if (empty($cHash)) {
			$configuration['features']['requireCHashArgumentForActionArguments'] = 0;
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
		$typoScriptFrontendController->content = $bootstrap->run('', $configuration);
		$isOutputting = !empty($typoScriptFrontendController->content) ? true : false;
		// Create a Response object when sending content
		$response = new Response();

		// Store session data for fe_users
		$typoScriptFrontendController->fe_user->storeSessionData();

		$response->getBody()->write($typoScriptFrontendController->content);

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
	protected function getContentDataArray($table, $uid)
	{

		/** @var \TYPO3\CMS\Core\Database\ConnectionPool $queryBuilder */
		$queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($table);
		$res = $queryBuilder->select('*')
			->from('tt_content')
			->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
			->groupBy('uid')
			->execute();
		$row = $res->fetch();

		return $row;
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
