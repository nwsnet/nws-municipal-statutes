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

use Nwsnet\NwsMunicipalStatutes\Dom\Converter;
use Nwsnet\NwsMunicipalStatutes\PageTitle\MunicipalPageTitleProvider;
use Nwsnet\NwsMunicipalStatutes\Pdf\Writer\LegalNormPdf;
use Nwsnet\NwsMunicipalStatutes\RestApi\JurisdictionFinder\JurisdictionFinder;
use Nwsnet\NwsMunicipalStatutes\RestApi\LocalLaw\LocalLaw;
use Nwsnet\NwsMunicipalStatutes\RestApi\RestClient;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
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
    protected $apiLocalLaw;

    /**
     * jurisdictionFinderApiCall get data
     *
     * @var JurisdictionFinder
     */
    protected $apiJurisdictionFinder;

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
     * deactivates flashmessages -> they are being generated for validation errrors for example
     *
     * @see ActionController::getErrorFlashMessage()
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * List view for legal standards with tree menu
     *
     * @throws UnsupportedRequestTypeException
     * @throws InvalidArgumentNameException
     * @throws NoSuchArgumentException
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
                    $this->request->setArgument($key, $value);
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
            $recursive = $this->configurationManager->getContentObject()->data['recursive'];
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
                'sortAttribute' => array('name')
            );
            if ($this->apiLocalLaw->legislator()->findAll($filter)->hasExceptionError()) {
                $error = $this->apiLocalLaw->legislator()->getExceptionError();
                throw new UnsupportedRequestTypeException($error['message'], $error['code']);
            }
            $legislator = $this->apiLocalLaw->getLegalNormByLegislator($this->apiLocalLaw->legislator()->getJsonDecode());
        }

        $treeMenu = $this->apiJurisdictionFinder->getTreeMenu($legislator);


        $legalNorm = array();
        //When a search request has been made
        if ($this->request->hasArgument('searchButton') && $this->request->hasArgument('search') && !$this->request->hasArgument('clearButton')) {
            if ($this->request->hasArgument('legislator')) {
                $search = $this->request->getArgument('search');
                $filter = array(
                    'legislatorId' => $this->request->getArgument('legislator'),
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
                        'jurisApprovalDate'
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
                $legalNorm = $this->apiLocalLaw->getLegalNormByStructure($this->request->getArgument('legislator'),
                    $legalNorm);
                $legalNorm['search'] = true;
                $legalNorm['currentSearch'] = $search;
                $this->userSession->saveSearch(array('searchButton' => 'search', 'search' => $search));
            }
        } else {
            $this->userSession->cleanSearch();
            if ($this->request->hasArgument('legislator')) {
                $filter = array(
                    'legislatorId' => $this->request->getArgument('legislator'),
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
                        'jurisApprovalDate'
                    ),
                    'sortAttribute' => 'longTitle'
                );
                if ($this->apiLocalLaw->legalNorm()->find($filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legalNorm()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm($this->request->getArgument('legislator'),
                    $legalNorm);
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

        $this->view->assign('treeMenu', $treeMenu);
        $this->view->assign('legalNorm', $legalNorm);
    }

    /**
     * Single view for legal norms without tree menu
     *
     * @throws InvalidArgumentNameException
     * @throws NoSuchArgumentException
     * @throws UnsupportedRequestTypeException
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
                    $this->request->setArgument($key, $value);
                }
            }
        }

        $legalNorm = array();
        //When a search request has been made
        if ($this->request->hasArgument('searchButton') && $this->request->hasArgument('search') && !$this->request->hasArgument('clearButton')) {
            if ($this->settings['legislatorId']) {
                $search = $this->request->getArgument('search');
                $filter = array(
                    'legislatorId' => $this->settings['legislatorId'],
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
                        'jurisApprovalDate'
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
                $legalNorm = $this->apiLocalLaw->getLegalNormByStructure($this->settings['legislatorId'],
                    $legalNorm);
                $legalNorm['search'] = true;
                $legalNorm['currentSearch'] = $search;
                $this->userSession->saveSearch(array('searchButton' => 'search', 'search' => $search));
            }
        } else {
            $this->userSession->cleanSearch();
            if ($this->settings['legislatorId']) {
                $filter = array(
                    'legislatorId' => $this->settings['legislatorId'],
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
                        'jurisApprovalDate'
                    ),
                    'sortAttribute' => 'longTitle'
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
                $legalNorm = $this->apiLocalLaw->getStructureByAllLegalNorm($this->settings['legislatorId'],
                    $legalNorm);
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
    }

    /**
     * Single view of the legal norm
     *
     * @throws UnsupportedRequestTypeException
     * @throws NoSuchArgumentException
     */
    public function showAction()
    {
        $legalNormId = 0;
        if ($this->request->hasArgument('legalnorm')) {
            $legalNormId = $this->request->getArgument('legalnorm');
        }

        if ($this->apiLocalLaw->legalNorm()->findById($legalNormId)->hasExceptionError()) {
            $error = $this->apiLocalLaw->legislator()->getExceptionError();
            throw new UnsupportedRequestTypeException($error['message'], $error['code']);
        }
        $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
        $legislatorId = $legalNorm['legislator']['id'];

        $legalNorm = $this->apiLocalLaw->getLegalNormWithStructure($legislatorId, $legalNorm);

        //Check if attachments exist and set document type
        if (isset($legalNorm['jurisAttachments']) && !empty($legalNorm['jurisAttachments'])) {
            foreach ($legalNorm['jurisAttachments'] as $key => $value) {
                if (strpos($value['mimeType'], '/') !== false) {
                    $legalNorm['jurisAttachments'][$key]['docType'] = substr($value['mimeType'],
                        strpos($value['mimeType'], '/') + 1
                    );
                }
            }
        }

        //HTML parser for the structure of the content
        /** @var Converter $converter */
        $converter = GeneralUtility::makeInstance(Converter::class);
        $legalNorm['parseContent'] = $converter->getContentArray($legalNorm['content']);


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
    }

    /**
     * Creates a PDF with table of contents of the legal norm
     *
     * @return string
     * @throws UnsupportedRequestTypeException
     * @throws NoSuchArgumentException
     */
    public function pdfAction()
    {
        $params = GeneralUtility::_GET();

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
            if (isset($params['tx_nwsmunicipalstatutes_pi1']['context']) && strpos($params['tx_nwsmunicipalstatutes_pi1']['context'],
                    '|') !== false) {
                list($table, $uid) = explode('|', $params['tx_nwsmunicipalstatutes_pi1']['context']);
            }
            //initalize the data us the content element
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
                throw new UnsupportedRequestTypeException($error['message'], $error['code']);
            }
            $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
            $legislatorId = $legalNorm['legislator']['id'];

            $legalNorm = $this->apiLocalLaw->getLegalNormWithStructure($legislatorId, $legalNorm);

            //Check if attachments exist and set document type
            if (isset($legalNorm['jurisAttachments']) && !empty($legalNorm['jurisAttachments'])) {
                foreach ($legalNorm['jurisAttachments'] as $key => $value) {
                    if (strpos($value['mimeType'], '/') !== false) {
                        $legalNorm['jurisAttachments'][$key]['docType'] = substr($value['mimeType'],
                            strpos($value['mimeType'], '/') + 1
                        );
                    }
                }
            }

            //HTML parser for the structure of the content
            /** @var Converter $converter */
            $converter = GeneralUtility::makeInstance(Converter::class);
            $legalNorm['parseContent'] = $converter->getContentArray($legalNorm['content']);

            //set absolute path for CSS and JS files for PDF creation
            $GLOBALS['TSFE']->absRefPrefix = $this->request->getBaseUri();

            $this->view->assign('settings', $settings);
            $this->view->assign('legalNorm', $legalNorm);
        } else {
            $params['tx_nwsmunicipalstatutes_pi1']['create'] = 'true';
            /* @var $cacheHash CacheHashCalculator */
            $cacheHash = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
            $cHash = $cacheHash->generateForParameters($this->httpBuildQuery($params));
            $params['cHash'] = $cHash;
            $uri = $this->request->getBaseUri() . 'index.php';
            /** @var RestClient $contentProvider */
            $contentProvider = GeneralUtility::makeInstance(RestClient::class);
            $html = $contentProvider->getData($uri, $params, true)->getResult();
            if (!empty($html)) {
                $filter = array(
                    'selectAttributes' => array(
                        'id',
                        'longTitle',
                        'shortTitle',

                    )
                );
                if ($this->apiLocalLaw->legalNorm()->findById($legalNormId, $filter)->hasExceptionError()) {
                    $error = $this->apiLocalLaw->legislator()->getExceptionError();
                    throw new UnsupportedRequestTypeException($error['message'], $error['code']);
                }
                $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();
                $fileName = !empty($legalNorm['shortTitle']) ? $legalNorm['shortTitle'] : $legalNorm['longTitle'];
                $fileName = $this->convertToSafeString($fileName);
                $fileName = substr($fileName, 0, self::MAX_ALIAS_LENGTH) . '.pdf';

                /** @var LegalNormPdf $pdfFile */
                $pdfFile = $this->objectManager->get(
                    'Nwsnet\\NwsMunicipalStatutes\\Pdf\\Writer\\LegalNormPdf'
                );
                $pdfFilePath = Environment::getPublicPath() . '/typo3temp/' . md5(mt_rand()) . '.pdf';
                if ($pdfFile->writeTo($pdfFilePath, $html) !== true) {
                    return '';
                }

                $pdf = @file_get_contents($pdfFilePath);
                unlink($pdfFilePath);

                /** @var TypoScriptFrontendController $typoScriptFrontendController */
                $typoScriptFrontendController = $GLOBALS['TSFE'];
                $typoScriptFrontendController->config['config']['additionalHeaders.']['10.']['header'] = 'Content-type: application/pdf';
                $typoScriptFrontendController->setContentType('application/pdf');

                $this->response->setHeader('Content-Transfer-Encoding', 'binary');
                $this->response->setHeader('Content-Disposition', 'attachment;filename="' . $fileName);
                $this->response->setHeader('Content-Length', strlen($pdf));
                $this->response->setHeader('Connection', 'close');
                echo $pdf;
            }
            return '';
        }
    }

    /**
     * Providing the legal norm name for the page and link title generation
     *
     * @return string NULL|$legalNorm['longTitle']
     */
    public function showTitleAction()
    {
        $request = $this->request->getArguments();
        $legalNormId = $request['legalnorm'];
        $filter = array(
            'selectAttributes' => array(
                'id',
                'longTitle'
            )
        );
        if ($this->apiLocalLaw->legalNorm()->findById($legalNormId, $filter)->hasExceptionError()) {
            return '';
        }
        $legalNorm = $this->apiLocalLaw->legalNorm()->getJsonDecode();

        if (isset($legalNorm['longTitle']) && !empty($legalNorm['longTitle'])) {
            return $legalNorm['longTitle'];
        }
        return '';
    }

    /**
     * Providing the legislator name for the page and link title generation
     *
     * @return string
     */
    public function showTitleLegislatorAction()
    {
        $request = $this->request->getArguments();
        $legislatorId = $request['legislator'];
        $filter = array(
            'selectAttributes' => array(
                'id',
                'name'
            )
        );
        if ($this->apiLocalLaw->legislator()->findById($legislatorId, $filter)->hasExceptionError()) {
            return '';
        }
        $legislator = $this->apiLocalLaw->legislator()->getJsonDecode();

        if (isset($legislator['name']) && !empty($legislator['name'])) {
            return $legislator['name'];
        }
        return '';
    }
}