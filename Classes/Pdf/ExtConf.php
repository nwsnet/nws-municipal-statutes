<?php
/**
 * Copyright notice
 *
 * (c) 2014-2016 Henning Kasch <hkasch@die-netzwerkstatt.de>, die NetzWerkstatt GmbH & Co. KG
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace Nwsnet\NwsMunicipalStatutes\Pdf;

class ExtConf
{
    /**
     * @var array
     */
    protected static $config;

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public static function get($key, $default = null)
    {
        if (null === self::$config) {
            self::$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nws_municipal_statutes']) ?: array();
        }

        if (isset(self::$config[$key])) {
            return self::$config[$key];
        } else {
            return $default;
        }
    }

    /**
     * @param string $key
     * @param string $delimiter
     * @param int $part
     * @param string|null $default
     * @return array|string|null
     */
    private static function splitAndGet($key, $delimiter, $part, $default = null)
    {
        $value = self::get($key, '');
        $parts = explode($delimiter, $value);
        if (isset($parts[$part])) {
            return $parts[$part];
        } else {
            return $default;
        }
    }

    /**
     * @param string $default
     * @return string
     */
    public static function getWkHtmlToPdfPath($default = 'wkhtmltopdf')
    {
        return self::get('wkhtmltopdfPath', $default);
    }
}
