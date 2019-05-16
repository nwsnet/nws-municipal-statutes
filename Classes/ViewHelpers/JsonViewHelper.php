<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Henning Kasch <typo3@die-netzwerkstatt.de>, die NetzWerkstatt GmbH & Co. KG
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

namespace Nwsnet\NwsMunicipalStatutes\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * For providing JSON data for maps etc.
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 */
class JsonViewHelper extends AbstractViewHelper
{

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments()
	{
		$this->registerArgument('subject', 'mixed', 'The object or array of objects to serialize');
		$this->registerArgument('mapping', 'array','The name of the properties, that should be imploded as array where key is the json object-key and value is the PHP object property. Can also be list of keys for a 1:1 mapping');
		$this->registerArgument('properties', 'string', 'List of properties to serialize (1:1 mapping)');
	}

	/**
	 * Render the tag.
	 *
	 * @return string
	 */
	public function render()
	{
		$subject = $this->arguments['subject'];
		if (empty($subject)) {
			$subject = $this->renderChildren();
		}

		$mapping = $this->arguments['mapping'] ?: array();

		if (!empty($this->arguments['properties'])) {
			$keys = GeneralUtility::trimExplode(',', $this->arguments['properties']);
			$mapping += array_combine($keys, $keys);
		}

		if (is_array($subject) || $subject instanceof \Traversable) {
			$ret = $this->objectCollectionToArray($subject, $mapping);
		} else {
			$ret = $this->objectToStdClass($subject, $mapping);
		}
		return json_encode($ret);
	}

	/**
	 * @param array $collection
	 * @param array $mapping
	 *
	 * @return array
	 */
	private function objectCollectionToArray($collection, array $mapping)
	{
		$ret = array();
		foreach ($collection as $key => $item) {
			if (is_array($item)) {
				$ret[$key] = $this->objectCollectionToArray($item, $mapping);
			} elseif (is_object($item)) {
				$ret[$key] = $this->objectToStdClass($item, $mapping);
			} else {
				$ret[$key] = $item;
			}
		}
		return $ret;
	}

	/**
	 * @param object $object
	 * @param array $mapping
	 *
	 * @return \stdClass
	 * @throws \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
	 */
	private function objectToStdClass($object, array $mapping)
	{
		$stdClass = new \stdClass();

		foreach ($mapping as $jsonKey => $property) {
			$stdClass->{$jsonKey} = ObjectAccess::getProperty($object, $property);
		}

		return $stdClass;
	}
}
