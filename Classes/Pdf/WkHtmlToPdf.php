<?php
/**
 * Copyright notice
 *
 * (c) 2014-2016 Henning Kasch <hkasch@die-netzwerkstatt.de>, Die NetzWerkstatt GmbH & Co. KG
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

class WkHtmlToPdf
{
    /**
     * @var string
     */
    protected $htmlContent;

    /**
     * Path to wkhtmltopdf binary
     *
     * @var string
     */
    protected $binPath;

    /**
     * @var resource
     */
    private $pdfStream;

    /**
     * @var array
     */
    protected $arguments = array(
        'margin-top' => 25,
        'margin-left' => 25,
        'margin-right' => 25,
        'margin-bottom' => 25,
        'print-media-type' => ''
    );

    /**
     * WkHtmlToPdf constructor.
     * @param $htmlContent
     * @param null $binPath
     */
    public function __construct($htmlContent, $binPath = null)
    {
        if ($binPath === null) {
            $binPath = ExtConf::getWkHtmlToPdfPath();
        }
        $this->binPath = $binPath;
        $this->htmlContent = $htmlContent;
    }

    /**
     * Close temporary stream correctly
     */
    public function __destruct()
    {
        if (is_resource($this->pdfStream)) {
            fclose($this->pdfStream);
        }
    }

    /**
     * @param $argument
     * @param string $value
     */
    public function setArgument($argument, $value = '')
    {
        $this->arguments[$argument] = $value;
    }

    /**
     * @param $argument
     */
    public function unsetArgument($argument)
    {
        if (isset($this->arguments[$argument])) {
            unset($this->arguments[$argument]);
        }
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function writeTo($path)
    {
        if (null === $this->pdfStream) {
            $this->pdfStream = $this->createPdfStream();
        }

        // check if stream creation didn't fail
        if ($this->pdfStream === false) {
            return false;
        }

        $bytesWritten = file_put_contents($path, $this->pdfStream);
        rewind($this->pdfStream);

        return $bytesWritten !== false;
    }

    /**
     * Generate the pdf on the fly from the content and writes a to a temporary stream
     * Returns either the stream containing the file or false on failure
     * Make sure to close the stream at the end of the process
     *
     * @return resource|bool
     */
    private function createPdfStream()
    {
        $html = $this->htmlContent;
        $command = sprintf('%s --quiet %s - -', $this->binPath, $this->getArgString());

        // will create a stream that will be kept inside memory until it reaches about 1MB
        $tempStream = fopen('php://temp', 'rw');

        $descriptorspec = array(
            0 => array('pipe', 'r'),  // STDIN
            1 => array('pipe', 'w'),  // STDOUT
            //2 => array('pipe', 'w') // STDERR
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            list($stdin, $stdout) = $pipes;

            // write html on the file to htmldoc
            // fclose() will start the conversion process
            fwrite($stdin, $html);
            fclose($stdin);

            stream_copy_to_stream($stdout, $tempStream);

            // close the output stream
            fclose($stdout);
        } else {
            fclose($tempStream);
            return false;
        }

        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            rewind($tempStream);
            return $tempStream;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    private function getArgString()
    {
        $arg = '';
        foreach ($this->arguments as $argument => $value) {
            $prefix = strlen($argument) === 1 ? '-' : '--';

            if (is_string($value) && strlen($value)) {
                $value = escapeshellarg($value);
            } elseif (!is_int($value)) {
                $value = '';
            }

            if ($arg) {
                $arg .= ' ';
            }

            $arg .= sprintf('%s%s %s', $prefix, $argument, $value);
        }
        return $arg;
    }
}
