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

use Exception;
use Nwsnet\NwsMunicipalStatutes\Exception\InvalidRequestMethodException;
use Nwsnet\NwsMunicipalStatutes\Exception\UnsupportedRequestTypeException;
use Nwsnet\NwsMunicipalStatutes\Session\UserSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface as ExtbaseResponseInterface;

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

    protected const DEFAULT_CONTROLLER = 'LocalLaw';

    protected const DEFAULT_ACTION = 'list';

    const MAX_ALIAS_LENGTH = 100;

    /**
     * Extension Name
     *
     * @var string
     */
    protected string $extensionName = 'NwsMunicipalStatutes';

    /**
     * @var string $extKey
     */
    protected string $extKey = 'nws_municipal_statutes';

    /**
     * UserSession
     *
     * @var UserSession
     */
    protected UserSession $userSession;

    /**
     * unknownErrorMessage
     *
     * @var string
     */
    protected string $unknownErrorMessage = 'An unknown error occurred. We´re about as soon as possible to resolve this faith.';

    /**
     * @var PageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * ApiCall set data
     *
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

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
     * @throws PageNotFoundException
     */
    public function callActionMethod(RequestInterface $request = null): ResponseInterface
    {
        try {
            if (empty($request)) {
                parent::callActionMethod();
                $response = new Response();
            } else {
                if ($this->getTypo3Version() < 12000000) {
                    $response = parent::callActionMethod($request);
                } else {
                    if ($this->validateControllerActionCall(
                        $request->getControllerName(),
                        $request->getControllerActionName()
                    )) {
                        $response = parent::callActionMethod($request);
                    } else {
                        $controllerActionMap = $this->mapSwitchableControllerActionsFromFlexForm();
                        if (count($controllerActionMap) === 0) {
                            $response = new Response();
                        } else {
                            $controller = key($controllerActionMap) ?? self::DEFAULT_CONTROLLER;
                            $action = $controllerActionMap[$controller][0] ?? self::DEFAULT_ACTION;
                            $response = new ForwardResponse($action);
                            $response = $response->withControllerName($controller);
                        }
                    }
                }
            }
        } catch (InvalidRequestMethodException $e) {
            $this->logger->debug($e->getMessage(), [$this->request->getControllerExtensionKey() => 2]);
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $GLOBALS['TYPO3_REQUEST'],
                $e->getMessage(),
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
            throw new ImmediateResponseException($response);
        } catch (UnsupportedRequestTypeException $e) {
            $this->logger->debug($e->getMessage(), [$this->request->getControllerExtensionKey() => 2]);
            $response = $this->handleError($e);
            if (!$response instanceof ResponseInterface) {
                $response = new Response();
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage(), [$this->request->getControllerExtensionKey() => 3]);
            throw $e;
        }

        return $response;
    }

    /**
     * Provide the error message for output within the page
     *
     * @param Exception $e
     * @return ResponseInterface|ExtbaseResponseInterface|null
     */
    protected function handleError(Exception $e)
    {
        if (method_exists($this, 'buildControllerContext')) {
            $controllerContext = $this->buildControllerContext();
            $controllerContext->getRequest()->setControllerName('Exception');
            $controllerContext->getRequest()->setControllerActionName('error');
            $this->view->setControllerContext($controllerContext);
        } else {
            $controllerContext = $this->view->getRenderingContext();
            $controllerContext->setControllerName('Exception');
            $controllerContext->setControllerAction('error');
        }
        $content = $this->view->assign('exception', $e)->render('error');
        if (method_exists($this, 'htmlResponse')) {
            return $this->htmlResponse($content);
        } else {
            $this->response->appendContent($content);

            return $this->response;
        }
    }

    /**
     * Gets the full TypoScript for the extension without it being overwritten with the "flexform"
     *
     * @return array $settings
     */
    protected function getTypoScript(): array
    {
        $configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

        return $typoScriptService->convertTypoScriptArrayToPlainArray(
            $configuration['plugin.']['tx_nwsmunicipalstatutes.']['settings.']
        );
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
    protected function overrideParameterFromTypoScript(array $overrideKeys, array $params, array $localSettings): array
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (!is_array($subValue)) {
                        if (key_exists($subKey, $overrideKeys)) {
                            if (empty($subValue) && !empty($localSettings[$key][$subKey] ?? null)) {
                                $params[$key][$subKey] = $localSettings[$key][$subKey];
                            }
                        }
                    }
                }
            } elseif (key_exists($key, $overrideKeys)) {
                if (empty($value) && !empty($localSettings[$key] ?? null)) {
                    $params[$key] = $localSettings[$key];
                }
            }
        }

        return $params;
    }

    /**
     * Create a query from arrays
     *
     * @param array $array
     * @param bool $qs
     * @return string
     */
    protected function httpBuildQuery(array $array, bool $qs = false): string
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
                        $parts[] = http_build_query(array($key.'['.$key2.']' => $value2));
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
    protected function convertToSafeString(
        string $processedTitle,
        string $spaceCharacter = '-',
        bool $strToLower = true
    ): string {
        /** @var CharsetConverter $csConvertor */
        $csConvertor = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
        if ($strToLower) {
            $processedTitle = mb_strtolower($processedTitle, 'UTF-8');
        }
        $processedTitle = strip_tags($processedTitle);
        $processedTitle = preg_replace('/[ \t\x{00A0}\-+_]+/u', $spaceCharacter, $processedTitle);
        $processedTitle = $csConvertor->specCharsToASCII('utf-8', $processedTitle);
        $processedTitle = preg_replace('/[^\p{L}0-9'.preg_quote($spaceCharacter).']/u', '', $processedTitle);
        $processedTitle = preg_replace('/'.preg_quote($spaceCharacter).'{2,}/', $spaceCharacter, $processedTitle);
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
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getContentDataArray(string $table, int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if ($table === 'pages') {
            $res = $queryBuilder->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        'list_type',
                        $queryBuilder->createNamedParameter('nwsmunicipalstatutes_pi1')
                    )
                );
        } else {
            $res = $queryBuilder->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
                );
        }
        if (method_exists($res, 'execute')) {
            $result = $res->execute()->fetch();
        } else {
            $result = current($res->executeQuery()->fetchAllAssociative());
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Retrieves and converts the FlexForm configuration data to an array.
     *
     * @return array The processed FlexForm configuration as an associative array. Returns an empty array if no FlexForm data is available.
     */
    protected function getFlexFormConfiguration(): array
    {
        if (method_exists($this->configurationManager, 'getContentObject')) {
            $contentObjectData = $this->configurationManager->getContentObject()->data;
        } else {
            $contentObjectData = $this->request->getAttribute('currentContentObject')->data;
        }

        /** @var FlexFormService $flexFormService */
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);

        return $flexFormService->convertFlexFormContentToArray($contentObjectData['pi_flexform'] ?? '') ?: [];
    }

    /**
     * Maps and parses switchable controller actions from a FlexForm configuration.
     *
     * @return array An associative array where each key is a controller name and the value is an array of associated actions.
     */
    protected function mapSwitchableControllerActionsFromFlexForm(): array
    {
        $flexFormConfiguration = $this->getFlexFormConfiguration();
        $actionsRaw = $flexFormConfiguration['switchableControllerActions'] ?? '';
        $actionsReformatted = str_replace(';', ',', $actionsRaw);
        $actionMappings = GeneralUtility::trimExplode(',', $actionsReformatted, true);

        return $this->parseControllerActions($actionMappings);
    }

    /**
     * Parses an array of controller-action mappings and organizes them into an associative array.
     *
     * @param array $actionMappings An array of strings where each string represents a controller-action mapping, separated by '->'.
     * @return array An associative array where each key is a controller name and the value is an array of associated actions.
     */
    protected function parseControllerActions(array $actionMappings): array
    {
        $controllerActionMap = [];

        foreach ($actionMappings as $mapping) {
            [$controller, $action] = GeneralUtility::trimExplode('->', $mapping);

            if (!empty($controller) && !empty($action)) {
                $controllerActionMap[$controller][] = $action;
            }
        }
        if (count($controllerActionMap) === 0) {
            $configuration = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            );
            if (isset($configuration['switchableControllerActions'])) {
                foreach ($configuration['switchableControllerActions'] as $controller => $actions) {
                    $controllerActionMap[$controller] = $actions;
                }
            }
        }

        return $controllerActionMap;
    }

    /**
     * Validates whether the given controller and action pair is allowed based on the configured controller-action map.
     *
     * @param string $controller The name of the controller to validate.
     * @param string $action The name of the action to validate.
     *
     * @return bool Returns true if the controller and action pair is valid, otherwise false.
     */
    private function validateControllerActionCall(string $controller, string $action): bool
    {
        $controllerActionMap = $this->mapSwitchableControllerActionsFromFlexForm();
        if (in_array($controller, array_keys($controllerActionMap), true)) {
            if (in_array($action, $controllerActionMap[$controller], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the TYPO3 version as an integer.
     *
     * @return int The TYPO3 version number converted to an integer.
     */
    protected function getTypo3Version(): int
    {
        if (defined('TYPO3_version')) {
            return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        } else {
            $version = VersionNumberUtility::getNumericTypo3Version();

            return VersionNumberUtility::convertVersionNumberToInteger($version);
        }
    }

    /**
     * Sets an argument in the request object.
     *
     * @param string $key The key of the argument to set.
     * @param mixed $value The value of the argument to set.
     *
     * @return void
     */
    protected function setArgument(string $key, $value): void
    {
        if (method_exists($this->request, 'withArgument')) {
            $this->request = $this->request->withArgument($key, $value);
        } else {
            $this->request->setArgument($key, $value);
        }
    }
}