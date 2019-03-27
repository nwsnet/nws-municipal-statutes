<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Dirk Meinke <typo3@die-netzwerkstatt.de>
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

namespace Nwsnet\NwsMunicipalStatutes\ViewHelpers\Uri;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;


/**
 * A view helper for creating Ajax URIs to extbase actions with contextRecord.
 *
 * = Examples =
 *
 * <code title="URI to the show-action of the current controller">
 *   <nws:uri.ajaxAction action="show" additionalParams="{eID:'nwsMunicipalStatutesDispatcher'}">
 *        Link
 *   </nws:uri.ajaxAction>
 * </code>
 * <output>
 *    index.php?id=123&eID=nwsMunicipalStatutesDispatcher&tx_myextension_plugin[context]=tt_content:123&tx_myextension_plugin[action]=show&tx_myextension_plugin[controller]=Standard&cHash=xyz
 *     (depending on the current page and your TS configuration)
 * </output>
 */
class AjaxActionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {
	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Arguments initialization
	 *
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
		$versionAsInt = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
		if ($versionAsInt > 8999999) {
			$this->registerArgument('action', 'string', 'Target action');
			$this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
			$this->registerArgument('extensionName', 'string', 'Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used');
			$this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
			$this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
			$this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter');
			$this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.');
			$this->registerArgument('noCacheHash', 'bool', 'Set this to suppress the cHash query parameter created by TypoLink. You should not need this.');
			$this->registerArgument('section', 'string', 'The anchor to be added to the URI');
			$this->registerArgument('format', 'string', 'The requested format, e.g. ".html');
			$this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.');
			$this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)');
			$this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute');
			$this->registerArgument('addQueryString', 'bool', 'If set, the current query parameters will be kept in the URI');
			$this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = TRUE');
			$this->registerArgument('addQueryStringMethod', 'string', 'Set which parameters will be kept. Only active if $addQueryString = TRUE');
			$this->registerArgument('arguments', 'array', 'Arguments for the controller action, associative array');
			$this->registerArgument('contextRecord', 'string', 'The record that the rendering should depend upon. e.g. current (default: record is fetched from current Extbase plugin), tt_content:12 (tt_content record with uid 12), pages:15 (pages record with uid 15), \'currentPage\' record of current page', FALSE, 'current');
		}
	}

	/**
	 * @param string $action                               Target action
	 * @param array  $arguments                            Arguments
	 * @param string $controller                           Target controller. If NULL current controllerName is used
	 * @param string $extensionName                        Target Extension Name (without "tx_" prefix and no
	 *                                                     underscores). If NULL the current extension name is used
	 * @param string $pluginName                           Target plugin. If empty, the current plugin name is used
	 * @param int    $pageUid                              target page. See TypoLink destination
	 * @param int    $pageType                             type of the target page. See typolink.parameter
	 * @param bool   $noCache                              set this to disable caching for the target page. You should
	 *                                                     not need this.
	 * @param bool   $noCacheHash                          set this to suppress the cHash query parameter created by
	 *                                                     TypoLink. You should not need this.
	 * @param string $section                              the anchor to be added to the URI
	 * @param string $format                               The requested format, e.g. ".html
	 * @param bool   $linkAccessRestrictedPages            If set, links pointing to access restricted pages will still
	 *                                                     link to the page even though the page cannot be accessed.
	 * @param array  $additionalParams                     additional query parameters that won't be prefixed like
	 *                                                     $arguments (overrule $arguments)
	 * @param bool   $absolute                             If set, the URI of the rendered link is absolute
	 * @param bool   $addQueryString                       If set, the current query parameters will be kept in the URI
	 * @param array  $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if
	 *                                                     $addQueryString = TRUE
	 * @param string $addQueryStringMethod                 Set which parameters will be kept. Only active if
	 *                                                     $addQueryString = TRUE
	 * @param string $contextRecord                        The record that the rendering should depend upon. e.g.
	 *                                                     current (default: record is fetched from current Extbase
	 *                                                     plugin), tt_content:12 (tt_content record with uid 12),
	 *                                                     pages:15 (pages record with uid 15), 'currentPage' record of
	 *                                                     current page
	 *
	 * @return string Rendered link
	 */
	public function render($action = NULL, array $arguments = [], $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $format = '', $linkAccessRestrictedPages = FALSE, array $additionalParams = [], $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = [], $addQueryStringMethod = NULL, $contextRecord = 'current') {

		$action = $this->arguments['action'];
		$controller = $this->arguments['controller'];
		$extensionName = $this->arguments['extensionName'];
		$pluginName = $this->arguments['pluginName'];
		$pageUid = (int)$this->arguments['pageUid'] ?: NULL;
		$pageType = (int)$this->arguments['pageType'];
		$noCache = (bool)$this->arguments['noCache'];
		$noCacheHash = (bool)$this->arguments['noCacheHash'];
		$section = (string)$this->arguments['section'];
		$format = (string)$this->arguments['format'];
		$linkAccessRestrictedPages = (bool)$this->arguments['linkAccessRestrictedPages'];
		$additionalParams = (array)$this->arguments['additionalParams'];
		$absolute = (bool)$this->arguments['absolute'];
		$addQueryString = (bool)$this->arguments['addQueryString'];
		$argumentsToBeExcludedFromQueryString = (array)$this->arguments['argumentsToBeExcludedFromQueryString'];
		$addQueryStringMethod = $this->arguments['addQueryStringMethod'];
		$arguments = $this->arguments['arguments'];
		$contextRecord = $this->arguments['contextRecord'];

		if ($pluginName === NULL) {
			$pluginName = $this->renderingContext->getControllerContext()->getRequest()->getPluginName();
		}
		if ($extensionName === NULL) {
			$extensionName = $this->renderingContext->getControllerContext()->getRequest()->getControllerExtensionName();
		}
		if ($contextRecord === 'current') {
			if (
				$pluginName !== $this->renderingContext->getControllerContext()->getRequest()->getPluginName()
				|| $extensionName !== $this->renderingContext->getControllerContext()->getRequest()->getControllerExtensionName()
			) {
				$contextRecord = 'currentPage';
			} else {
				$contextRecord = $this->configurationManager->getContentObject()->currentRecord;
				if (empty($contextRecord)) {
					$contextRecord = $this->renderingContext->getControllerContext()->getRequest()->getArgument('context');
				}
			}
		}
		$arguments['context'] = $contextRecord;

		$uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
		$uri = $uriBuilder
			->reset()
			->setTargetPageUid($pageUid)
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setUseCacheHash(!$noCacheHash)
			->setSection($section)
			->setFormat($format)
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->setAddQueryStringMethod($addQueryStringMethod)
			->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());
		$this->tag->forceClosingTag(TRUE);
		return $this->tag->render();
	}
}
