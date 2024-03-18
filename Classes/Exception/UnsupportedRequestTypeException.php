<?php
/*******************************************************************************
 * Copyright notice
 *
 * (c) 2020 Bjoern Wilke <typo3@die-netzwerkstatt.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ******************************************************************************/

namespace Nwsnet\NwsMunicipalStatutes\Exception;


use Nwsnet\NwsMunicipalStatutes\Exception;
use Throwable;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Invalid call API
 *
 * Class UnsupportedRequestTypeException
 * @package Nwsnet\NwsMunicipalStatutes\Exception
 */
class UnsupportedRequestTypeException extends Exception
{
    /**
     * extensionName
     *
     * @var string
     */
    private $extensionName = 'NwsRegionalMap';

    /**
     * ApiTypeNotAvailableException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->code = 1602158550;
        $this->message = LocalizationUtility::translate(
            'template.exception.couldNotInitializeApi',
            $this->extensionName
        );
    }
}
