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
 * Class JavascriptOptimizer
 * @package Sethorax\T3essentials\Page
 */
class JavascriptOptimizer {

    /**
     * @var array
     */
    protected $scriptsToOptimize;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceCompressor
     */
    protected $compressor;


    /**
     * Adds the given $scripts to the scriptsToOptimize array.
     *
     * @param $scripts
     * @param $isTag
     */
    public function addScripts($scripts, $isTag) {
        if ($isTag) {
            $paths = SimpleHtmlParser::getAttributesFromTagsWithinHtmlString($scripts, 'script', 'src');
            foreach ($paths as $path) {
                $this->scriptsToOptimize[] = $this->removeQueryStringFromPath($path);
            }
        }
    }

    /**
     * Concatenates all added scripts via the TYPO3 compressor and returns the script data array.
     *
     * @return array
     */
    public function optimize() {
        $scriptData = $this->createScriptDataArray($this->scriptsToOptimize);
        $optimizedScriptData = $this->getCompressor()->concatenateJsFiles($scriptData);

        return $optimizedScriptData;
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
     * @param $scripts
     * @return array
     */
    private function createScriptDataArray($scripts) {
        $scriptData = [];

        if (is_array($scripts)) {
            foreach ($scripts as $key => $value) {
                $scriptData[$key] = [
                    'file' => $value,
                    'type' => 'text/javascript',
                    'section' => 2,
                    'compress' => true,
                    'excludeFromConcatenation' => false
                ];
            }
        }

        return $scriptData;
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