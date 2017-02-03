<?php

namespace Sethorax\T3essentials\Html;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class SimpleHtmlParser
 * @package Sethorax\T3essentials\Html
 */
class SimpleHtmlParser {
    /**
     * @var \DOMDocument
     */
    protected $htmlDocument;


    /**
     * SimpleHtmlParser constructor.
     *
     * Initializes new DOMDocument and loads the given $html into the DOMDocument
     * The DOMDocument is saved as a class property
     *
     * @param $html
     */
    public function __construct($html) {
        if (empty($html)) {
            $html = '<html></html>';
        }

        $this->htmlDocument = new \DOMDocument;
        libxml_use_internal_errors(true);
        $this->htmlDocument->loadHTML($html);
    }


    /**
     * Returns an array of $attribute values from all $tags withing $html
     *
     * @param $html
     * @param $tag
     * @param $attribute
     * @return array
     */
    public static function getAttributesFromTagsWithinHtmlString($html, $tag, $attribute) {
        $parser = new SimpleHtmlParser($html);
        $elements = $parser->getTagsByName($tag);
        return $parser->getAttributeFromElements($elements, $attribute);
    }


    /**
     * Returns all the elements with the given $tagName within the DOMDocument
     *
     * @param $tagName
     * @return \DOMNodeList
     */
    public function getTagsByName($tagName) {
        return $this->htmlDocument->getElementsByTagName($tagName);
    }

    /**
     * Returns the values of the specified $attribute from the given $elements as an array.
     *
     * @param $elements
     * @param $attribute
     * @return array
     */
    public function getAttributeFromElements($elements, $attribute) {
        $attributes = [];

        foreach ($elements as $element) {
            $attributes[] = $element->getAttribute($attribute);
        }

        return $attributes;
    }
}