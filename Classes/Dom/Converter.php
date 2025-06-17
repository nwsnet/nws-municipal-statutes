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

namespace Nwsnet\NwsMunicipalStatutes\Dom;

use SimpleHtmlDom\simple_html_dom_node;

use function SimpleHtmlDom\str_get_html;

/**
 * Converter for delivering content items by SimpleHtmlDom
 *
 * @package    SimpleHtmlDom
 * @subpackage nws_municipal_statutes
 *
 */
class Converter
{
    /**
     * Parses a html content and returns a formatted content item as an array
     *
     * @param $html
     * @return array $ccontent
     */
    public function getContentArray($html)
    {
        if (!function_exists('SimpleHtmlDom\str_get_html')) {
            require_once __DIR__.'/SimpleHtmlDom.php';
        }
        $content = array();
        $dom = str_get_html($html);
        // Get navigation elements
        foreach ($dom->find('nav a') as $element) {
            $section = str_replace('#', '', $element->href);
            $name = $element->plaintext;
            $content['nav'][] = array('name' => $name, 'section' => $section);
        }
        // Get content elements
        foreach ($dom->find('section[class]') as $elements) {
            $header = $this->findFirstChildNode('header', $elements);
            $section = $this->findFirstChildNode('section', $elements);
            if (!empty($header)) {
                $data['section'] = $this->getChildNodesData(
                    'a',
                    array(
                        'getAttribute' => 'href',
                        'remove' => '#',
                    ),
                    $header
                );
                $data['headline'] = $this->getChildNodesData(
                    'a',
                    array(
                        'plaintext' => 'href',
                    ),
                    $header
                );
                $header = $data;
            }
            if (!empty($section)) {
                $section = $this->setTag('h2', 'h4', $section);
            }
            $content['elements'][] = array('header' => $header, 'content' => $section->innertext ?? '');
        }
        foreach ($dom->find('footer') as $element) {
            $content['elements'][] = array('header' => '', 'content' => $element->innertext ?? '');
        }

        return $content;
    }

    /**
     * Change the tag elment of all nodes
     *
     * @param string $tag
     * @param string $tagReplace
     * @param simple_html_dom_node $node
     * @return simple_html_dom_node
     */
    protected function setTag($tag, $tagReplace, simple_html_dom_node $node)
    {
        /**
         * @var string $key
         * @var simple_html_dom_node $e
         */
        foreach ($node->children as $key => $e) {
            if ($e->tag == $tag) {
                $e->tag = $tagReplace;
                if ($e->hasChildNodes()) {
                    $node->children[$key] = $this->setTag($tag, $tagReplace, $e);
                }
            } else {
                if ($e->hasChildNodes()) {
                    $node->children[$key] = $this->setTag($tag, $tagReplace, $e);
                }
            }
        }

        return $node;
    }

    /**
     * Finds the first node of the dom element
     *
     * @param string $tag
     * @param simple_html_dom_node $node
     * @return bool|simple_html_dom_node
     */
    protected function findFirstChildNode($tag, simple_html_dom_node $node)
    {
        $child = false;
        /**
         * @var string $key
         * @var simple_html_dom_node $e
         */
        foreach ($node->children as $key => $e) {
            if ($e->tag == $tag) {
                return $e;
            } else {
                if ($e->hasChildNodes()) {
                    $this->findFirstChildNode($tag, $e);
                }
            }
        }

        return $child;
    }

    /**
     * Gets attributes and manipulates the content
     *
     * @param string $tag to the searching tag
     * @param array $filter filter for fetching attributes and possible conversions
     * Example: array(
     *        'getAttribute' => 'href',
     *        'remove' => '#'
     * )
     * @param simple_html_dom_node $node
     * @return bool|mixed|string
     */
    protected function getChildNodesData($tag, array $filter, simple_html_dom_node $node)
    {
        $data = '';
        /**
         * @var string $key
         * @var simple_html_dom_node $e
         */
        foreach ($node->children as $key => $e) {
            if ($e->tag == $tag) {
                foreach ($filter as $typ => $value) {
                    switch ($typ) {
                        case 'getAttribute':
                            $data = $e->getAttribute($value);
                            break;
                        case 'remove':
                            $data = str_replace($value, '', $data);
                            break;
                        case 'plaintext':
                            $data = $e->plaintext;
                    }
                }

                return $data;
            } else {
                if ($e->hasChildNodes()) {
                    $data = $this->getChildNodesData($tag, $filter, $e);
                }
            }
        }

        return $data;
    }
}