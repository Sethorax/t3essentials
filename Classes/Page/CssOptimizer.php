<?php

namespace Sethorax\T3essentials\Page;

use Sethorax\T3essentials\Html\SimpleHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

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
 * Class CssOptimizer
 * @package Sethorax\T3essentials\Page
 */
class CssOptimizer {

    /**
     * @var array
     */
    protected $stylesToOptimize;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceCompressor
     */
    protected $compressor;


    /**
     * Adds the given $styles to the stylesToOptimize array.
     *
     * @param $styles
     * @param $isTag
     */
    public function addStyles($styles, $isTag) {
        if ($isTag) {
            $paths = SimpleHtmlParser::getAttributesFromTagsWithinHtmlString($styles, 'link', 'href');
            foreach ($paths as $path) {
                $this->stylesToOptimize[] = $this->removeQueryStringFromPath($path);
            }
        }
    }

    /**
     * Concatenates all added styles via the TYPO3 compressor and returns the style data array.
     *
     * @return array
     */
    public function optimize() {
        $styleData = $this->createStyleDataArray($this->stylesToOptimize);
        $optimizedStyleData = $this->getCompressor()->concatenateCssFiles($styleData);

        return $optimizedStyleData;
    }


    /**
     * Removes the query string from the file path.
     *
     * @param $path
     * @return string
     */
    private function removeQueryStringFromPath($path) {
        return substr($path, 0, strpos($path, '?'));
    }

    /**
     * Creates the array structure with default settings for the TYPO3 compressor.
     *
     * @param $styles
     * @return array
     */
    private function createStyleDataArray($styles) {
        $styleData = [];

        if (is_array($styles)) {
            foreach ($styles as $key => $value) {
                $styleData[$key] = [
                    'file' => $value,
                    'type' => 'text/css',
                    'section' => 2,
                    'compress' => true,
                    'excludeFromConcatenation' => false
                ];
            }
        }

        return $styleData;
    }

    /**
     * Returns instance of \TYPO3\CMS\Core\Resource\ResourceCompressor
     *
     * @return \TYPO3\CMS\Core\Resource\ResourceCompressor
     */
    private function getCompressor()
    {
        if ($this->compressor === null) {
            $this->compressor = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceCompressor::class);
        }
        return $this->compressor;
    }
}