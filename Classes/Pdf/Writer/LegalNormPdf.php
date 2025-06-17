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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
     * Creates the PDF document from the HTML content and saves it in the specified path
     *
     * @param string $path
     * @param string $html
     *
     * @return bool
     */
    public function writeTo(string $path, string $html): bool
    {

        if ($this->getTypo3Version() < 12000000) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var WkHtmlToPdf $pdf */
            $pdf = $objectManager->get(WkHtmlToPdf::class, $html);
        } else {
            /** @var WkHtmlToPdf $pdf */
            $pdf = GeneralUtility::makeInstance(WkHtmlToPdf::class, $html);
        }
        $pdf->setArgument('footer-right', 'Seite [page] von [topage]');
        $pdf->setArgument('footer-spacing', 5);
        $pdf->setArgument('enable-internal-links');
        $pdf->setArgument('disable-external-links');
        if ($this->isBasicAuth()) {
            $auth = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth'];
            $pdf->setArgument('username', $auth['username']);
            $pdf->setArgument('password', $auth['password']);
        }
        // Set the proxy if entered the TYPO3 configuration
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
    private function isBasicAuth(): bool
    {
        $check = false;

        //check whether parameters for authentication are available
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth'])) {
            $check = true;
        }

        return $check;
    }

    /**
     * Retrieves the TYPO3 version as an integer.
     *
     * @return int The TYPO3 version number converted to an integer.
     */
    private function getTypo3Version(): int
    {
        if (defined('TYPO3_version')) {
            return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
        } else {
            $version = VersionNumberUtility::getNumericTypo3Version();

            return VersionNumberUtility::convertVersionNumberToInteger($version);
        }
    }
}
