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

namespace Nwsnet\NwsMunicipalStatutes\ViewHelpers;

use Traversable;

/**
 * A view helper for creating an option group select list.
 *
 * @package    TYPO3
 * @subpackage nws_municipal_statutes
 *
 */

/**
 * = Examples =
 *
 * <code>
 * {namespace nws=Nwsnet\NwsMunicipalStatutes\ViewHelpers}
 * <nws:select options="{optgroup}" value="{currentOption}" />
 * </code>
 *
 * <output>
 * <select>
 *    <optgroup label="name">
 *        <option value="value">option</option>
 *    </optgroup>
 * </select>
 * </output>
 */
class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{

    /**
     * @var array
     */
    protected $selectOptions = array();

    /**
     * Render the tag.
     *
     * @return string rendered tag.
     * @api
     */
    public function render()
    {
        // convert options array into fluid options array to get selectFieldViewHelper work under 6.2.11
        if (is_array($this->arguments['options']) || $this->arguments['options'] instanceof Traversable) {
            $this->selectOptions = $this->arguments['options'];
            if (method_exists($this, "hasArgument")) { //for smaller TYPO3 6.1
                $options = array();
                $this->arguments['options'] = $options;
            }
        }
        return parent::render();
    }

    /**
     * Initialize arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        if (!method_exists($this, "hasArgument")) { //for smaller TYPO3 6.1
            $this->registerArgument('prependOptionLabel', 'string',
                'If specified, will provide an option at first position with the specified label.');
            $this->registerArgument('prependOptionValue', 'string',
                'If specified, will provide an option at first position with the specified value.');
        }
    }

    /**
     * Render the option tags.
     *
     * @param array $options the options or with option groups for the form.
     *
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        if (method_exists($this, "hasArgument")) { //for smaller TYPO3 6.1
            if ($this->hasArgument('prependOptionLabel')) {
                $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
                $label = $this->arguments['prependOptionLabel'];
                $output .= $this->renderOptionTag($value, $label, false) . chr(10);
            }
        } else {
            if ($this->arguments->hasArgument('prependOptionLabel')) {
                $value = $this->arguments->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
                $label = $this->arguments['prependOptionLabel'];
                $output .= $this->renderOptionTag($value, $label, false) . chr(10);
            }
        }
        foreach ($this->selectOptions as $value => $label) {
            if (is_array($label)) {
                $optgroup = $optionsList = '';
                foreach ($label as $keyOptGroup) {
                    if (is_string($keyOptGroup)) {
                        $optgroup = $keyOptGroup;
                    } else {
                        foreach ($keyOptGroup as $keyValue => $keyLabel) {
                            $isSelected = $this->isSelected($keyValue);
                            $optionsList .= $this->renderOptionTag($keyValue, $keyLabel, $isSelected) . chr(10);
                        }
                    }
                }
                if (!empty($optionsList) && !empty($optgroup)) {
                    $output .= $this->renderOptionGroupTag($optgroup, $optionsList);
                } elseif (!empty($optionsList)) {
                    $output .= $optionsList;
                }
            } else {
                $isSelected = $this->isSelected($value);
                $output .= $this->renderOptionTag($value, $label, $isSelected) . chr(10);
            }
        }
        return $output;
    }

    /**
     * Render the option group tag.
     *
     * @param string $label the label for the option group.
     * @param string $optionsList the rendering options list
     *
     * @return string rendered tag.
     */
    protected function renderOptionGroupTag($label, $optionsList)
    {
        return '<optgroup label="' . htmlspecialchars($label) . '">' . $optionsList . '</optgroup>';
    }
}