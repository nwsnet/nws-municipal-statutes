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

use Doctrine\DBAL\Exception;
use Nwsnet\NwsMunicipalStatutes\Dom\Converter;
use Nwsnet\NwsMunicipalStatutes\Exception\InvalidRequestMethodException;
use Nwsnet\NwsMunicipalStatutes\Exception\UnsupportedRequestTypeException;
use Nwsnet\NwsMunicipalStatutes\PageTitle\MunicipalPageTitleProvider;
use Nwsnet\NwsMunicipalStatutes\Pdf\Writer\LegalNormPdf;
use Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw;
use Nwsnet\NwsMunicipalStatutes\RestApi\RestClient;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Events Controller for the delivery of event data
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class LocalLawController extends AbstractController
{
    /**
     * localLawApiCall get data
     *
     * @var LocalLaw
     */
    protected LocalLaw $apiLocalLaw;

    /**
     * jurisdictionFinderApiCall get data
     *
     * @var JurisdictionFinder
     */
    protected JurisdictionFinder $apiJurisdictionFinder;

    /**
     * @param LocalLaw $apiLocalLaw
     */
    public function injectApiLocalLaw(LocalLaw $apiLocalLaw)
    {
        $this->apiLocalLaw = $apiLocalLaw;
    }

    /**
     * @param JurisdictionFinder $apiJurisdictionFinder
     */
    public function injectApiJurisdictionFinder(JurisdictionFinder $apiJurisdictionFinder)
    {
        $this->apiJurisdictionFinder = $apiJurisdictionFinder;
    }

    /**
     * deactivates flash messages -> they are being generated for validation errors for example
     *
     * @see ActionController::getErrorFlashMessage()
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }

    /**
     * List view for legal standards with tree menu
     *
     * @return ResponseInterface|void
     *
     * @throws UnsupportedRequestTypeException
     * @throws \Exception
     */
    public function listAction()
    {
        //Empty session data when the first call
        if (count($this->request->getArguments()) == 0) {
            $this->userSession->cleanSearch();
            $this->userSession->cleanReferrer();
        }
        //Check if a search session exists
        if (!$this->request->hasArgument('searchButton') && !$this->request->hasArgument('clearButton')) {
            $search = $this->userSession->getSearch();
            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    $this->setArgument($key, $value);
                }
            }
        }

        //when legislators have been selected
        if (!empty($this->settings['legislatorIds'])) {
            if (strpos($this->settings['legislatorIds'], ',') !== false) {
                $items = explode(',', $this->settings['legislatorIds']);
            } else {
                $items[] = $this->settings['legislatorIds'];
            }
            $legislator['count'] = count($items);
            $legislator['results'] = $items;
            if (method_exists($this->configurationManager, 'getContentObject')) {
                $contentObjectData = $this->configurationManager->getContentObject()->data;
            } else {
                $contentObjectData = $this->request->getAttribute('currentContentObject')->data;
            }
            $recursive = $contentObjectData['recursive'] ?? [];
            //From the legislators of the multiple selection recursively determine the parents entries of the legislators
            if ($this->settings['recursiveSelection']) {
                $areas = $this->apiLocalLaw->getAreasByLegislatorId($legislator);
                $areas = $this->apiJurisdictionFinder->getAreasRecursiveByAreas($areas, $recursive);
                if (isset($areas['stopId'])) {
                    $this->apiJurisdictionFinder->setStopId($areas['stopId']);
                }
                if (isset($areas['results'])) {
                    $legislator = $this->apiLocalLaw->mergeLegislatorByAreas($areas['results']);
                }
            } else {
                $legislator = $this->apiLocalLaw->getLegalNormByLegislatorId($legislator);
            }
        } else {
            $filter = array(
                'sortAttribute' => array('name'),
            );
            if ($this->apiLocalLaw->legislator()->findAll($filter)->hasExceptionError()) {
                $error = $this->apiLocalLaw->legislator()->getExceptionError();
                throw new UnsupportedRequestTypeException($error['message'], $error['code']);
            }
            $legislator = $this->apiLocalLaw->getLegalNormByLegislator(
                $this->apiLocalLaw->legislator()->getJsonDecode()
            );
        }

        $treeMenu = $this->apiJurisdictionFinder->getTreeMenu($legislator);


        $legalNorm = array();
        //When a search request has been made
        if ($this->request->hasArgument('searchButton') && $this->request->hasArgument(
                'search'
            ) && !$this->request->hasArgument('clearButton')) {
            if ($this->request->hasArgument('legislator')) {
                $search = $this->request->getArgument('search');
                $filter = array(
                    'legislatorIds' => [$this->request->getArgument('legislator')],
                    'selectAttributes' => array(
                        'id',
                        'categories',
                        'structureNodes',
                        'longTitle',
                        'jurisPromulgationDate',
                        'jurisAmendDate',
                        'jurisEnactmentFrom',
                        'jurisEnactmentTo',
                        'jurisPublicationDate',
                        'jurisApprovalDate',
                        'jurisNormScopes',
                    ),
                    'sortAttribute' => 'longTitle',
                    'searchWord' => $search,
                    'searchFullText' => 'true',

                );
                if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $legalNorm = $this->apiLocalLaw->getLegalNormByStructure(
                    $this->request->getArgument('legislator'),
                    $legalNorm
                );
                $legalNorm['search'] = true;
                $legalNorm['currentSearch'] = $search;
                $this->userSession->saveSearch(array('searchButton' => 'search', 'search' => $search));
            }
        } else {
            $this->userSession->cleanSearch();
            if ($this->request->hasArgument('legislator')) {
                $filter = array(
                    'legislatorIds' => [$this->request->getArgument('legislator')],
                    'selectAttributes' => array(
                        'id',
                        'categories',
                        'structureNodes',
                        'longTitle',
                        'jurisPromulgationDate',
                        'jurisAmendDate',
                        'jurisEnactmentFrom',
                        'jurisEnactmentTo',
                        'jurisPublicationDate',
                        'jurisApprovalDate',
                        'jurisNormScopes',
                    ),
                    'sortAttribute' => 'longTitle',
                );
                if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm(
                    $this->request->getArgument('legislator'),
                    $legalNorm
                );
            }
        }
        //Save referrer data for transmission
        if (!empty($this->settings['showSingleViewPid'] ?? 0)) {
            $page = array();
            $this->userSession->cleanReferrer();
            $page['controllerName'] = $this->request->getControllerName();
            $page['actionName'] = $this->request->getControllerActionName();
            $page['extensionName'] = $this->request->getControllerExtensionName();
            $page['pid'] = $GLOBALS['TSFE']->id;
            $page['arguments'] = $this->request->getArguments();
            $this->userSession->saveReferrer($page);
        }

        //Set the page title
        if (isset($legalNorm['name'])) {
            $titleProvider = GeneralUtility::makeInstance(MunicipalPageTitleProvider::class);
            $titleProvider->setTitle($legalNorm['name']);
        }

        $this->view->assign('treeMenu', $treeMenu);
        $this->view->assign('legalNorm', $legalNorm);

        if (method_exists($this, 'htmlResponse')) {
            return $this->htmlResponse();
        }
    }

    /**
     * Single view for legal norms without tree menu
     *
     * @return ResponseInterface|void
     *
     * @throws UnsupportedRequestTypeException
     * @throws \Exception
     */
    public function singlelistAction()
    {
        //Empty session data when the first call
        if (count($this->request->getArguments()) == 0) {
            $this->userSession->cleanSearch();
            $this->userSession->cleanReferrer();
        }
        //Check if a search session exists
        if (!$this->request->hasArgument('searchButton') && !$this->request->hasArgument('clearButton')) {
            $search = $this->userSession->getSearch();
            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    $this->setArgument($key, $value);
                }
            }
        }

        $legalNorm = array();
        //When a search request has been made
        if ($this->request->hasArgument('searchButton') && $this->request->hasArgument(
                'search'
            ) && !$this->request->hasArgument('clearButton')) {
            if ($this->settings['legislatorId']) {
                $search = $this->request->getArgument('search');
                $filter = array(
                    'legislatorIds' => [$this->settings['legislatorId']],
                    'selectAttributes' => array(
                        'id',
                        'categories',
                        'structureNodes',
                        'longTitle',
                        'jurisPromulgationDate',
                        'jurisAmendDate',
                        'jurisEnactmentFrom',
                        'jurisEnactmentTo',
                        'jurisPublicationDate',
                        'jurisApprovalDate',
                        'jurisNormScopes',
                    ),
                    'sortAttribute' => 'longTitle',
                    'searchWord' => $search,
                    'searchFullText' => 'true',

                );
                if ($this->settings['structureId']) {
                    if (strpos($this->settings['structureId'], ',') !== false) {
                        $ids = explode(',', $this->settings['structureId']);
                        $filter['structureIds'] = $ids;
                    } else {
                        $filter['structureIds'] = array($this->settings['structureId']);
                    }
                }

                if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $legalNorm = $this->apiLocalLaw->getLegalNormByStructure(
                    $this->settings['legislatorId'],
                    $legalNorm
                );
                $legalNorm['search'] = true;
                $legalNorm['currentSearch'] = $search;
                $this->userSession->saveSearch(array('searchButton' => 'search', 'search' => $search));
            }
        } else {
            $this->userSession->cleanSearch();
            if ($this->settings['legislatorId']) {
                $filter = array(
                    'legislatorIds' => [$this->settings['legislatorId']],
                    'selectAttributes' => array(
                        'id',
                        'categories',
                        'structureNodes',
                        'longTitle',
                        'jurisPromulgationDate',
                        'jurisAmendDate',
                        'jurisEnactmentFrom',
                        'jurisEnactmentTo',
                        'jurisPublicationDate',
                        'jurisApprovalDate',
                        'jurisNormScopes',
                    ),
                    'sortAttribute' => 'longTitle',
                );
                if ($this->settings['structureId']) {
                    if (strpos($this->settings['structureId'], ',') !== false) {
                        $ids = explode(',', $this->settings['structureId']);
                        $filter['structureIds'] = $ids;
                    } else {
                        $filter['structureIds'] = array($this->settings['structureId']);
                    }
                }
                if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm(
                    $this->settings['legislatorId'],
                    $legalNorm
                );
            }
        }
        //Save referrer data for transmission
        if (isset($this->settings['showSingleViewPid']) && !empty($this->settings['showSingleViewPid'])) {
            $page = array();
            $this->userSession->cleanReferrer();
            $page['controllerName'] = $this->request->getControllerName();
            $page['actionName'] = $this->request->getControllerActionName();
            $page['extensionName'] = $this->request->getControllerExtensionName();
            $page['pid'] = $GLOBALS['TSFE']->id;
            $page['arguments'] = $this->request->getArguments();
            $this->userSession->saveReferrer($page);
        }

        //Set the page title
        if (isset($legalNorm['name'])) {
            $titleProvider = GeneralUtility::makeInstance(MunicipalPageTitleProvider::class);
            $titleProvider->setTitle($legalNorm['name']);
        }

        $this->view->assign('legalNorm', $legalNorm);

        if (method_exists($this, 'htmlResponse')) {
            return $this->htmlResponse();
        }
    }

    /**
     * Single view of the legal norm
     *
     * @return ResponseInterface|void
     *
     * @throws UnsupportedRequestTypeException
     * @throws InvalidRequestMethodException
     */
    public function showAction()
    {
        $legalNormId = 0;
        if ($this->request->hasArgument('legalnorm')) {
            $legalNormId = $this->request->getArgument('legalnorm');
        }

        if ($this->apiLocalLaw->legalNorm()->findById($legalNormId)->hasExceptionError()) {
            $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
            if ($error['code'] === 404) {
                throw new InvalidRequestMethodException($error['message'], $error['code']);
            }
            throw new UnsupportedRequestTypeException($error['message'], $error['code']);
        }
        $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
        $legislatorId = $legalNorm['legislator']['id'];

        $legalNorm = $this->apiLocalLaw->getLegalNormWithStructure($legislatorId, $legalNorm);

        //Check if attachments exist and set document type
        if (!empty($legalNorm['jurisAttachments'] ?? null)) {
            foreach ($legalNorm['jurisAttachments'] as $key => $value) {
                if (strpos($value['mimeType'], '/') !== false) {
                    $legalNorm['jurisAttachments'][$key]['docType'] = substr(
                        $value['mimeType'],
                        strpos($value['mimeType'], '/') + 1
                    );
                }
            }
        }
        if ($this->apiLocalLaw->legalNorm()->findByIdHtml($legalNormId)->hasExceptionError()) {
            $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
            throw new UnsupportedRequestTypeException($error['message'], $error['code']);
        }
        $htmlContent = $this->apiLocalLaw->legalNorm()->getResult();
        //HTML parser for the structure of the content
        /** @var Converter $converter */
        $converter = GeneralUtility::makeInstance(Converter::class);
        $legalNorm['parseContent'] = $converter->getContentArray($htmlContent);


        //Set the page title for the page and the search
        if (isset($legalNorm['longTitle'])) {
            $GLOBALS['TSFE']->page['title'] = $legalNorm['longTitle'];
            $GLOBALS['TSFE']->indexedDocTitle = $legalNorm['longTitle'];
            $titleProvider = GeneralUtility::makeInstance(MunicipalPageTitleProvider::class);
            $titleProvider->setTitle($legalNorm['longTitle']);
        }

        //Get referrer data from the transmission
        $referrer = $this->userSession->getReferrer();
        $this->view->assign('referrer', $referrer);

        $this->view->assign('legalNorm', $legalNorm);

        if (method_exists($this, 'htmlResponse')) {
            return $this->htmlResponse();
        }
    }

    /**
     * Creates a PDF with table of contents of the legal norm
     *
     * @return string|ResponseInterface|void
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     * @throws UnsupportedRequestTypeException
     */
    public function pdfAction()
    {
        if (method_exists(GeneralUtility::class, '_GET')) {
            $params = GeneralUtility::_GET();
        } else {
            $params = $this->request->getQueryParams();
        }

        if (!isset($params['id'])) {
            $params['id'] = $GLOBALS['TYPO3_REQUEST']->getAttribute('routing')->getPageId();
            $params['type'] = $GLOBALS['TYPO3_REQUEST']->getAttribute('routing')->getPageType();
        }

        if (isset($params['cHash'])) {
            unset($params['cHash']);
        }

        $legalNormId = 0;
        if ($this->request->hasArgument('legalnorm')) {
            $legalNormId = $this->request->getArgument('legalnorm');
        }

        if ($this->request->hasArgument('create')) {
            $settings = array();

            //Read ContextRecord for Flexform
            if (isset($params['tx_nwsmunicipalstatutes_pi1']['context']) && strpos(
                    $params['tx_nwsmunicipalstatutes_pi1']['context'],
                    '|'
                ) !== false) {
                list($table, $uid) = explode('|', $params['tx_nwsmunicipalstatutes_pi1']['context']);
            }
            //initialize the data us the content element
            if (isset($table) && isset($uid)) {
                $data = $this->getContentDataArray($table, $uid);
                if (isset($data['pi_flexform'])) {
                    /** @var FlexFormService $flexFormService */
                    $flexFormService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Service\\FlexFormService');
                    $flexform = $flexFormService->convertFlexFormContentToArray($data['pi_flexform']);
                    if (isset($flexform['settings'])) {
                        $settings = array_merge($this->settings, $flexform['settings']);
                    }
                }
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                /** @var FileRepository $fileObjects */
                $fileObjects = $fileRepository->findByRelation('tt_content', 'image', $uid);
                if (isset($fileObjects[0])) {
                    $settings['headlineImage'] = $fileObjects[0];
                } else {
                    $settings['headlineImage'] = null;
                }
            }
            if ($this->apiLocalLaw->legalNorm()->findById($legalNormId)->hasExceptionError()) {
                $error = $this->apiLocalLaw->legislator()->getExceptionError();
                throw new \UnexpectedValueException($error['message'], $error['code']);
            }
            $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
            $legislatorId = $legalNorm['legislator']['id'];

            $legalNorm = $this->apiLocalLaw->getLegalNormWithStructure($legislatorId, $legalNorm);

            //Check if attachments exist and set document type
            if (!empty($legalNorm['jurisAttachments'] ?? null)) {
                foreach ($legalNorm['jurisAttachments'] as $key => $value) {
                    if (strpos($value['mimeType'], '/') !== false) {
                        $legalNorm['jurisAttachments'][$key]['docType'] = substr(
                            $value['mimeType'],
                            strpos($value['mimeType'], '/') + 1
                        );
                    }
                }
            }

            if ($this->apiLocalLaw->legalNorm()->findByIdHtml($legalNormId)->hasExceptionError()) {
                $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                throw new UnsupportedRequestTypeException($error['message'], $error['code']);
            }
            $htmlContent = $this->apiLocalLaw->legalNorm()->getResult();
            //HTML parser for the structure of the content
            /** @var Converter $converter */
            $converter = GeneralUtility::makeInstance(Converter::class);
            $legalNorm['parseContent'] = $converter->getContentArray($htmlContent);

            //set absolute path for CSS and JS files for PDF creation
            if ($this->getTypo3Version() < 12000000) {
                $GLOBALS['TSFE']->absRefPrefix = $this->request->getBaseUri();
            } else {
                $normalizedParams = $this->request->getAttribute('normalizedParams');
                $GLOBALS['TSFE']->absRefPrefix = $normalizedParams->getSiteUrl();
            }

            $this->view->assign('settings', $settings);
            $this->view->assign('legalNorm', $legalNorm);
            if (method_exists($this, 'htmlResponse')) {
                return $this->htmlResponse();
            }
        } else {
            /** @var TypoScriptFrontendController $typoScriptFrontendController */
            $typoScriptFrontendController = $GLOBALS['TSFE'];
            $typoScriptFrontendController->config['config']['disableAllHeaderCode'] = 0;
            $params['tx_nwsmunicipalstatutes_pi1']['create'] = 1;
            /* @var $cacheHash CacheHashCalculator */
            $cacheHash = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
            $cHash = $cacheHash->generateForParameters($this->httpBuildQuery($params));
            $params['cHash'] = $cHash;
            //set absolute path for CSS and JS files for PDF creation
            if ($this->getTypo3Version() < 12000000) {
                $uri = $this->request->getBaseUri().'index.php';
            } else {
                $normalizedParams = $this->request->getAttribute('normalizedParams');
                $uri = $normalizedParams->getSiteUrl().'index.php';
            }

            /** @var RestClient $contentProvider */
            $contentProvider = GeneralUtility::makeInstance(RestClient::class);
            $html = $contentProvider->getData($uri, $params, true)->getResult();
            if (!empty($html)) {
                $filter = array(
                    'selectAttributes' => array(
                        'id',
                        'longTitle',
                        'shortTitle',

                    ),
                );
                if ($this->apiLocalLaw->legalNorm()->findById($legalNormId, $filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legislator()->getExceptionError();
                    throw new \UnexpectedValueException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $fileName = !empty($legalNorm['shortTitle']) ? $legalNorm['shortTitle'] : $legalNorm['longTitle'];
                $fileName = $this->convertToSafeString($fileName);
                $fileName = substr($fileName, 0, self::MAX_ALIAS_LENGTH).'.pdf';

                if (property_exists($this, 'objectManager')) {
                    /** @var LegalNormPdf $pdfFile */
                    $pdfFile = $this->objectManager->get(LegalNormPdf::class);
                } else {
                    /** @var LegalNormPdf $pdfFile */
                    $pdfFile = GeneralUtility::makeInstance(LegalNormPdf::class);
                }

                $pdfFilePath = Environment::getPublicPath().'/typo3temp/'.md5(mt_rand()).'.pdf';
                if ($pdfFile->writeTo($pdfFilePath, $html) !== true) {
                    return '';
                }

                $pdf = @file_get_contents($pdfFilePath);
                unlink($pdfFilePath);
                $typoScriptFrontendController->config['config']['additionalHeaders.']['10.']['header'] = 'Content-type: application/pdf';
                $typoScriptFrontendController->setContentType('application/pdf');
                if (method_exists($this, 'htmlResponse')) {
                    $typoScriptFrontendController->config['config']['disableAllHeaderCode'] = 1;

                    return $this->responseFactory->createResponse()
                        ->withHeader('Content-Type', 'application/pdf')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment;filename="'.$fileName)
                        ->withHeader('Content-Length', (string)strlen($pdf))
                        ->withHeader('Connection', 'close')
                        ->withBody($this->streamFactory->createStream((string)($pdf)));
                } else {
                    $this->response->setHeader('Content-Transfer-Encoding', 'binary');
                    $this->response->setHeader('Content-Disposition', 'attachment;filename="'.$fileName);
                    $this->response->setHeader('Content-Length', strlen($pdf));
                    $this->response->setHeader('Connection', 'close');
                    echo $pdf;
                }
            }

            return '';
        }
    }

    /**
     * Providing the legal norm name for the page and link title generation
     *
     * @return string|ResponseInterface
     */
    public function showTitleAction(int $legalnorm)
    {
        $legalNormId = $legalnorm;
        $title = '';
        $filter = array(
            'selectAttributes' => array(
                'id',
                'longTitle',
            ),
        );
        if ($this->apiLocalLaw->legalNorm()->findById($legalNormId, $filter)->hasExceptionError()) {
            return '';
        }
        $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();

        if (!empty($legalNorm['longTitle'] ?? null)) {
            $title = $legalNorm['longTitle'];
        }
        if (method_exists($this, 'htmlResponse')) {
            return $this->responseFactory->createResponse()
                ->withBody($this->streamFactory->createStream((string)($title)));
        } else {
            return $title;
        }
    }

    /**
     * Providing the legislator name for the page and link title generation
     *
     * @return string|ResponseInterface
     */
    public function showTitleLegislatorAction(int $legislator)
    {
        $legislatorId = $legislator;
        $title = '';
        $filter = array(
            'selectAttributes' => array(
                'id',
                'name',
            ),
        );
        if ($this->apiLocalLaw->legislator()->findById($legislatorId, $filter)->hasExceptionError()) {
            return '';
        }
        $legislator = $this->apiLocalLaw->legislator()->getJsonDecode();

        if (!empty($legislator['name'] ?? null)) {
            $title = $legislator['name'];
        }
        if (method_exists($this, 'htmlResponse')) {
            return $this->responseFactory->createResponse()
                ->withBody($this->streamFactory->createStream((string)($title)));
        } else {
            return $title;
        }
    }
}