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

use Nwsnet\NwsMunicipalStatutes\Session\UserSession;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

/**
 * Abstract controllers that provide the error messages
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
abstract class AbstractController extends ActionController
{

	const MAX_ALIAS_LENGTH = 100;
	/**
	 * UserSession
	 *
	 * @var \Nwsnet\NwsMunicipalStatutes\Session\UserSession
	 */
	protected $userSession;

	/**
	 * unknownErrorMessage
	 *
	 * @var string
	 */
	protected $unknownErrorMessage = 'An unknown error occurred. We´re about as soon as possible to resolve this faith.';

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * ApiCall set data
	 *
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \Nwsnet\NwsMunicipalStatutes\Session\UserSession $userSession
	 */
	public function injectUserSession(UserSession $userSession)
	{
		$this->userSession = $userSession;
	}

	/**
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
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
	 * Calls the specified action method and passes the arguments.
	 *
	 * @return void
	 * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
	 */
	protected function callActionMethod()
	{
		try {
			parent::callActionMethod();
		} catch (\Exception $exception) {
			//write log in the TYPO3
			GeneralUtility::devLog($exception->getMessage(), $this->request->getControllerExtensionKey(), 2);
			//When an action in the controller is called, which produces only a direct output without Tempplate then no page called found
			if ($exception instanceof InvalidRequestMethodException) {
				$GLOBALS['TSFE']->pageNotFoundAndExit($exception->getMessage());
			} elseif ($exception instanceof UnsupportedRequestTypeException) {
				//We append the error message to the response. This causes the error message to be displayed inside the normal page layout. WARNING: the plugins output may gets cached.
				if ($this->response instanceof Response) {
					$this->response->setStatus(500);
				}
				$this->handleError($exception);
			}
		}
	}

	/**
	 * provide the error message for output within the page
	 *
	 * @param \Exception $e
	 *
	 * @return void
	 */
	protected function handleError(\Exception $e)
	{
		$controllerContext = $this->buildControllerContext();
		$controllerContext->getRequest()->setControllerName('Exception');
		$controllerContext->getRequest()->setControllerActionName('error');
		$this->view->setControllerContext($controllerContext);
		$content = $this->view->assign('exception', $e)->render('error');
		$this->response->appendContent($content);
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
		/** @var \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService */
		$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

		$settings = $typoScriptService->convertTypoScriptArrayToPlainArray($configuration['plugin.']['tx_nwsmunicipalstatutes.']['settings.']);
		return $settings;
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
		/** @var \TYPO3\CMS\Core\Charset\CharsetConverter $csConvertor */
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
	 */
	protected function getContentDataArray($table, $uid)
	{
		if (isset($GLOBALS['TYPO3_DB'])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . $uid);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		} else {
			/** @var \TYPO3\CMS\Core\Database\ConnectionPool $queryBuilder */
			$queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($table);
			$res = $queryBuilder->select('*')
				->from('tt_content')
				->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
				->groupBy('uid')
				->execute();
			$row = $res->fetch();
		}
		return $row;
	}
}