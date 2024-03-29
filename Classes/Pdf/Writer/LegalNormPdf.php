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

namespace Nwsnet\NwsMunicipalStatutes\Pdf\Writer;

use Nwsnet\NwsMunicipalStatutes\Pdf\WkHtmlToPdf;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * Class LegalNormPdf
 *
 * Creates a PDF from the legal norm from an HTML document
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class LegalNormPdf
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * LegalNormPdf constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates the PDF document from the HTML content and saves it in the specified path
     *
     * @param $path
     * @param $html
     *
     * @return bool
     */
    public function writeTo($path, $html)
    {
        /** @var WkHtmlToPdf $pdf */
        $pdf = $this->objectManager->get('Nwsnet\\NwsMunicipalStatutes\\Pdf\\WkHtmlToPdf', $html);
        $pdf->setArgument('footer-right', 'Seite [page] von [topage]');
        $pdf->setArgument('footer-spacing', 5);
        $pdf->setArgument('enable-internal-links', '');
        $pdf->setArgument('disable-external-links', '');
        if ($this->isBasicAuth()) {
            $auth = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth'];
            $pdf->setArgument('username', $auth['username']);
            $pdf->setArgument('password', $auth['password']);
        }
        // Set the proxy if entered in the TYPO3 configuration
        $proxy = trim($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'] ?? '');
        if (!empty($proxy)) {
            $pdf->setArgument('p', $proxy);
        }

        $success = $pdf->writeTo($path);

        // force garbage collection
        unset($pdf);

        return $success;
    }

    /**
     * Check a basic authentication is required
     *
     * @return bool
     */
    private function isBasicAuth()
    {
        $check = false;

        //check whether parameters for authentication are available
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth'])) {
            $check = true;
        }

        return $check;
    }
}
