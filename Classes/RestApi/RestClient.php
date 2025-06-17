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

namespace Nwsnet\NwsMunicipalStatutes\RestApi;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RestClient
 * For using a FullRest API
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */
class RestClient
{
    /**
     * Api Url
     *
     * @var string $http
     */
    private string $http = '';

    /**
     * Api Key
     *
     * @var string $apiKey
     */
    private string $apiKey = '';

    /**
     * additional header information for submission
     *
     * @var array $additionalHeaders
     */
    private array $additionalHeaders = [];

    /**
     * Json return from the response
     *
     * @var string $result
     */
    private string $result;

    /**
     * Error transferring to the API
     *
     * @var array $exceptionError
     */
    private array $exceptionError = [];

    /**
     * Representation of the curl call
     *
     * @var resource $curl
     */
    private $curl;

    /**
     * Provides the configuration of the call
     *
     * @param array $config
     */
    public function setConfiguration(array $config = array())
    {
        //set baseUrl
        if (isset($config['http'])) {
            $this->http = $config['http'];
        }

        //set ApiKey
        if (isset($config['apiKey'])) {
            $this->apiKey = $config['apiKey'];
        }
        //set additionalHeaders
        if (isset($config['additionalHeaders']) && is_array($config['additionalHeaders'])) {
            $this->additionalHeaders = $config['additionalHeaders'];
        }
    }

    /**
     * Returns the data from the Api
     *
     * @param string $path
     * @param array $filter
     * @param bool $noHeader
     * @return RestClient
     */
    public function getData(string $path, array $filter, bool $noHeader = false): RestClient
    {
        $this->setConnection($path, $filter, $noHeader);

        return $this->executeCurl();
    }

    /**
     * Establishes the connection to the interface
     *
     * @param string $path
     * @param array $filter
     * @param bool $noHeader
     */
    protected function setConnection(string $path, array $filter, bool $noHeader = false)
    {
        $header = array();
        $executeHttp = $this->http.$path.'?'.$this->httpBuildQuery($filter);
        if (empty($noHeader)) {
            $header = array(
                "Content-type: application/json",
                "api_key: ".$this->apiKey,
            );
        }

        if (!empty($this->additionalHeaders)) {
            $additionalHeaders = array();
            foreach ($this->additionalHeaders as $key => $value) {
                $additionalHeaders[] = $key.': '.$value;
            }
            $header = array_merge($header, $additionalHeaders);
        }

        // Set the proxy if entered the TYPO3 configuration
        $proxy = trim($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'] ?? '');

        $this->curl = curl_init($executeHttp);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl, CURLOPT_REFERER, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        if (!empty($proxy)) {
            curl_setopt($this->curl, CURLOPT_PROXY, $proxy);
        }
        if ($this->isInternalCallAndBasicAuth($executeHttp)) {
            $auth = $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth'];
            curl_setopt($this->curl, CURLOPT_USERPWD, $auth['username'].':'.$auth['password']);
        }
    }

    /**
     * Executes the curl call
     *
     * @return RestClient
     */
    protected function executeCurl(): RestClient
    {
        $curl_response = curl_exec($this->curl);
        $header = curl_getinfo($this->curl);
        if ($header['http_code'] !== 200) {
            switch ($header['http_code']) {
                case 404:
                    $message = empty($curl_response) ? 'Not found' : strip_tags($curl_response);
                    break;
                case 401:
                    $message = empty($curl_response) ? 'Unauthorized' : strip_tags($curl_response);
                    break;
                default:
                    $message = empty($curl_response) ? 'Unknown' : strip_tags($curl_response);
                    break;
            }
            $error = array(
                'message' => $message,
                'code' => $header['http_code'],
            );
            $this->setExceptionError($error);
        }

        curl_close($this->curl);
        $this->setResult($curl_response);

        return $this;
    }

    /**
     * Check it is an internal call and whether a basic authentication is required
     *
     * @param $url
     * @return bool
     */
    private function isInternalCallAndBasicAuth($url): bool
    {
        $check = false;
        if (GeneralUtility::isOnCurrentHost($url)) {
            //check whether parameters for authentication are available
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth']) && is_array(
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['BasicAuth']
                )) {
                $check = true;
            }
        }

        return $check;
    }

    /**
     * Create a query from arrays
     *
     * @param $array
     * @param bool $qs
     * @return string
     */
    private function httpBuildQuery($array, bool $qs = false): string
    {
        $parts = array();
        if ($qs) {
            $parts[] = $qs;
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_integer($key2)) {
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
     * Returns the json string
     *
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Put the json string
     *
     * @param string $result
     */
    public function setResult(string $result)
    {
        $this->result = $result;
    }


    /**
     * Returns the exception error
     *
     * @return array $exceptionError
     */
    public function getExceptionError(): array
    {
        return $this->exceptionError;
    }

    /**
     * Sets the exception error
     *
     * @param array $exceptionError
     *
     * @return void
     */
    public function setExceptionError(array $exceptionError)
    {
        $this->exceptionError = $exceptionError;
    }

    /**
     * Check whether errors exist
     *
     * @return boolean
     */
    public function hasExceptionError(): bool
    {
        $error = $this->getExceptionError();
        if (empty($error)) {
            return false;
        }

        return true;
    }

    /**
     * Decode the json representation
     *
     * @return array|boolean
     */
    public function getJsonDecode()
    {
        $json = $this->getResult();
        if (!empty($json)) {
            return json_decode($json, true);
        }

        return false;
    }

    /**
     * Encode to the json representation
     *
     * @param mixed $data
     *
     * @return string
     */
    public function jsonEncode($data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Recursive search of an array to a value
     *
     * @param string $needle (search string)
     * @param array $haystack (to be searched)
     *
     * @return false|int
     */
    public function recursiveArraySearch(string $needle, array $haystack)
    {
        foreach ($haystack as $key => $value) {
            $current_key = $key;
            if ($needle === $value or (is_array($value) && $this->recursiveArraySearch($needle, $value) !== false)) {
                return $current_key;
            }
        }

        return false;
    }
}