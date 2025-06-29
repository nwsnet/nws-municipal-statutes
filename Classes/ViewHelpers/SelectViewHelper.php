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
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

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
final class SelectViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var mixed
     */
    protected $selectedValue;
    /**
     * @var array|mixed|Traversable
     */
    private $selectOptions;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute(
            'size',
            'string',
            'Size of select field, a numeric value to show the amount of items to be visible at the same time - equivalent to HTML <select> site attribute'
        );
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
        $this->registerArgument(
            'options',
            'array',
            'Associative array with internal IDs as key, and the values are displayed in the select box. Can be combined with or replaced by child f:form.select.* nodes.'
        );
        $this->registerArgument(
            'optionsAfterContent',
            'boolean',
            'If true, places auto-generated option tags after those rendered in the tag content. If false, automatic options come first.',
            false,
            false
        );
        $this->registerArgument(
            'optionValueField',
            'string',
            'If specified, will call the appropriate getter on each object to determine the value.'
        );
        $this->registerArgument(
            'optionLabelField',
            'string',
            'If specified, will call the appropriate getter on each object to determine the label.'
        );
        $this->registerArgument('sortByOptionLabel', 'boolean', 'If true, List will be sorted by label.', false, false);
        $this->registerArgument(
            'selectAllByDefault',
            'boolean',
            'If specified options are selected if none was set before.',
            false,
            false
        );
        $this->registerArgument(
            'errorClass',
            'string',
            'CSS class to set if there are errors for this ViewHelper',
            false,
            'f3-form-error'
        );
        $this->registerArgument(
            'prependOptionLabel',
            'string',
            'If specified, will provide an option at first position with the specified label.'
        );
        $this->registerArgument(
            'prependOptionValue',
            'string',
            'If specified, will provide an option at first position with the specified value.'
        );
        $this->registerArgument('multiple', 'boolean', 'If set multiple options may be selected.', false, false);
        $this->registerArgument('required', 'boolean', 'If set no empty value is allowed.', false, false);
    }

    public function render(): string
    {
        if ($this->arguments['required']) {
            $this->tag->addAttribute('required', 'required');
        }
        $name = $this->getName();
        if ($this->arguments['multiple']) {
            $this->tag->addAttribute('multiple', 'multiple');
            $name .= '[]';
        }
        $this->tag->addAttribute('name', $name);

        // convert options array into fluid options array to get selectFieldViewHelper work under 6.2.11
        if (is_array($this->arguments['options']) || $this->arguments['options'] instanceof Traversable) {
            $this->selectOptions = $this->arguments['options'];
            if (method_exists($this, "hasArgument")) { //for smaller TYPO3 6.1
                $options = array();
                $this->arguments['options'] = $options;
            }
        }
        $options = $this->getOptions();

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();
        $content = '';

        // register field name for token generation.
        $this->registerFieldNameForFormTokenGeneration($name);
        // in case it is a multi-select, we need to register the field name
        // as often as there are elements in the box
        if ($this->arguments['multiple']) {
            $content .= $this->renderHiddenFieldForEmptyValue();
            // Register the field name additional times as required by the total number of
            // options. Since we already registered it once above, we start the counter at 1
            // instead of 0.
            $optionsCount = count($options);
            for ($i = 1; $i < $optionsCount; $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }
            // save the parent field name so that any child f:form.select.option
            // tag will know to call registerFieldNameForFormTokenGeneration
            // this is the reason why "self::class" is used instead of static::class (no LSB)
            $viewHelperVariableContainer->addOrUpdate(
                self::class,
                'registerFieldNameForFormTokenGeneration',
                $name
            );
        }

        $viewHelperVariableContainer->addOrUpdate(self::class, 'selectedValue', $this->getSelectedValue());
        $prependContent = $this->renderPrependOptionTag();
        $tagContent = $this->renderOptionTags();
        $childContent = $this->renderChildren();
        $viewHelperVariableContainer->remove(self::class, 'selectedValue');
        $viewHelperVariableContainer->remove(self::class, 'registerFieldNameForFormTokenGeneration');
        if (isset($this->arguments['optionsAfterContent']) && $this->arguments['optionsAfterContent']) {
            $tagContent = $childContent.$tagContent;
        } else {
            $tagContent .= $childContent;
        }
        $tagContent = $prependContent.$tagContent;

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($tagContent);
        $content .= $this->tag->render();

        return $content;
    }

    /**
     * Render prepended option tag
     */
    protected function renderPrependOptionTag(): string
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            $label = $this->arguments['prependOptionLabel'];
            $output .= $this->renderOptionTag((string)$value, (string)$label, false).LF;
        }

        return $output;
    }

    /**
     * Render the option tags.
     *
     *
     * @return string rendered tags.
     */
    protected function renderOptionTags(): string
    {
        $output = '';
        foreach ($this->selectOptions as $value => $label) {
            if (is_array($label)) {
                $optgroup = $optionsList = '';
                foreach ($label as $keyOptGroup) {
                    if (is_string($keyOptGroup)) {
                        $optgroup = $keyOptGroup;
                    } else {
                        foreach ($keyOptGroup as $keyValue => $keyLabel) {
                            $isSelected = $this->isSelected($keyValue);
                            $optionsList .= $this->renderOptionTag($keyValue, $keyLabel, $isSelected).chr(10);
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
                $output .= $this->renderOptionTag($value, $label, $isSelected).chr(10);
            }
        }

        return $output;
    }

    /**
     * Render the option tags.
     *
     * @return array An associative array of options, key will be the value of the option tag
     */
    protected function getOptions(): array
    {
        if (!is_array($this->arguments['options']) && !$this->arguments['options'] instanceof \Traversable) {
            return [];
        }
        $options = [];
        $optionsArgument = $this->arguments['options'];
        foreach ($optionsArgument as $key => $value) {
            if (!is_object($value) && !is_array($value)) {
                $options[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                if (!$this->hasArgument('optionValueField')) {
                    throw new \InvalidArgumentException(
                        'Missing parameter "optionValueField" in SelectViewHelper for array value options.', 1682693720
                    );
                }
                if (!$this->hasArgument('optionLabelField')) {
                    throw new \InvalidArgumentException(
                        'Missing parameter "optionLabelField" in SelectViewHelper for array value options.', 1682693721
                    );
                }
                $key = ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
                $value = ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
                $options[$key] = $value;
                continue;
            }
            if ($this->hasArgument('optionValueField')) {
                $key = ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
                if (is_object($key)) {
                    if (method_exists($key, '__toString')) {
                        $key = (string)$key;
                    } else {
                        throw new Exception(
                            'Identifying value for object of class "'.gettype($value).'" was an object.',
                            1247827428
                        );
                    }
                }
            } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                // @todo use $this->persistenceManager->isNewObject() once it is implemented
                $key = $this->persistenceManager->getIdentifierByObject($value);
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $key = (string)$value;
            } elseif (is_object($value)) {
                throw new Exception(
                    'No identifying value for object of class "'.get_class($value).'" found.',
                    1247826696
                );
            }
            if ($this->hasArgument('optionLabelField')) {
                $value = ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
                if (is_object($value)) {
                    if (method_exists($value, '__toString')) {
                        $value = (string)$value;
                    } else {
                        throw new Exception(
                            'Label value for object of class "'.get_class(
                                $value
                            ).'" was an object without a __toString() method.', 1247827553
                        );
                    }
                }
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $value = (string)$value;
            } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                // @todo use $this->persistenceManager->isNewObject() once it is implemented
                $value = $this->persistenceManager->getIdentifierByObject($value);
            }
            $options[$key] = $value;
        }
        if ($this->arguments['sortByOptionLabel']) {
            asort($options, SORT_LOCALE_STRING);
        }

        return $options;
    }

    /**
     * Render the option tags.
     *
     * @param mixed $value Value to check for
     * @return bool True if the value should be marked as selected.
     */
    protected function isSelected($value): bool
    {
        $selectedValue = $this->getSelectedValue();
        if ($value === $selectedValue || (string)$value === $selectedValue) {
            return true;
        }
        if ($this->hasArgument('multiple')) {
            if ($selectedValue === null && $this->arguments['selectAllByDefault'] === true) {
                return true;
            }
            if (is_array($selectedValue) && in_array($value, $selectedValue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the selected value(s)
     *
     * @return array|string|null value string or an array of strings
     */
    protected function getSelectedValue()
    {
        $this->setRespectSubmittedDataValue(true);
        $value = $this->getValueAttribute();
        if (!is_array($value) && !$value instanceof \Traversable) {
            return $this->getOptionValueScalar($value);
        }
        $selectedValues = [];
        foreach ($value as $selectedValueElement) {
            $selectedValues[] = $this->getOptionValueScalar($selectedValueElement);
        }

        return $selectedValues;
    }

    /**
     * Get the option value for an object
     *
     * @param mixed $valueElement
     * @return string @todo: Does not always return string ...
     */
    protected function getOptionValueScalar($valueElement): ?string
    {
        if (is_object($valueElement)) {
            if ($this->hasArgument('optionValueField')) {
                return ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
            }
            // @todo use $this->persistenceManager->isNewObject() once it is implemented
            if ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
                return $this->persistenceManager->getIdentifierByObject($valueElement);
            }

            return (string)$valueElement;
        }

        return $valueElement;
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @param bool $isSelected specifies whether to add selected attribute
     * @return string the rendered option tag
     */
    protected function renderOptionTag(string $value, string $label, bool $isSelected): string
    {
        $output = '<option value="'.htmlspecialchars($value).'"';
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'.htmlspecialchars($label).'</option>';

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
    protected function renderOptionGroupTag(string $label, string $optionsList): string
    {
        return '<optgroup label="'.htmlspecialchars($label).'">'.$optionsList.'</optgroup>';
    }
}