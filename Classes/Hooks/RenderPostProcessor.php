<?php

namespace Sethorax\T3essentials\Hooks;

use Sethorax\T3essentials\Html\SimpleHtmlParser;
use Sethorax\T3essentials\Page\CssOptimizer;
use Sethorax\T3essentials\Page\JavascriptOptimizer;
use Sethorax\T3essentials\Service\TyposcriptLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
 * Class RenderPostProcessor
 * @package Sethorax\T3essentials\Hooks
 */
class RenderPostProcessor {
    protected $params;

    protected $settings;

    protected $headerData;

    protected $footerData;

    /**
     * Render method when page is being generated.
     *
     * @return void
     */
    public function process($params) {
        if (TYPO3_MODE == 'FE') {
            $this->params = $params;
            $this->settings = TyposcriptLoader::getSetup('t3essentials', false);

            if (is_array($this->settings)) {
                $this->checkTyposcriptSettings();
            }

            return $this->params;
        } else {
            return $params;
        }
    }

    /**
     * Check sthe typoscript setting for this extension.
     * Calls functions accordingly.
     */
    private function checkTyposcriptSettings() {
        if (is_array($this->settings['page']['js']['inline'])) {
            $this->addInlineJs();
        }

        if (!empty($this->settings['page']['css']['critical'])) {
            $this->addCriticalCss();
        }

        if (!empty($this->settings['page']['viewport'])) {
            $this->setViewportMetaTag();
        }

        if ($this->settings['seo']['setCanonicalUrl'] == '1') {
            $this->setCanonicalUrl();
        }

        if ($this->settings['seo']['hrefLang']['enable'] == '1') {
            $this->addHrefLangUrls();
        }

        if ($this->settings['performance']['enable'] == '1') {
            if ($this->settings['performance']['js']['enable'] == '1') {
                if ($this->settings['performance']['js']['moveJsToBottom']) {
                    $this->moveJsToBottom();
                }

                $this->concatenateFooterJS();
            }

            if ($this->settings['performance']['css']['enable'] == '1') {
                $this->concatenateStyles();
            }

            if (is_array($this->settings['performance']['dnsPrefetch']['urls']) || $this->settings['performance']['dnsPrefetch']['autoAddLibraries'] == '1') {
                $this->prepareDnsPrefetchLinks();
            }
        }

        if (is_array($this->settings['services']['googleFonts']['fonts'])) {
            $this->addGoogleFonts();
        }

        if ($this->settings['services']['googleAnalytics']['enable'] == '1') {
            $this->addAnalyticsScript();
        }


        $this->addHeaderData();
        $this->addFooterData();
    }

    private function addInlineJs() {
        foreach ($this->settings['page']['js']['inline'] as $inlineJS) {
            $script = '<script>' . $inlineJS['script'] . '</script>';
            if ($inlineJS['includeInHead'] == '1') {
                $this->headerData['inlineJS'][] = $script;
            } else {
                $this->footerData['inlineJS'][] = $script;
            }
        }
    }

    private function addCriticalCss() {
        $file = GeneralUtility::getFileAbsFileName($this->settings['page']['css']['critical']);

        if (file_exists($file)) {
            if ($this->settings['performance']['enable'] == '1' && $this->settings['performance']['css']['enable'] == '1') {
                $contents = file_get_contents($file);
                $this->headerData['25-criticalCss'][] = '<style t3essentials-critical-css>' . $contents . '</style>';
            } else {
                $relPath = PathUtility::getRelativePathTo($file);
                $relPath = substr($relPath, 0, strlen($relPath) - 1) . '?' . filemtime($file);

                $this->headerData['25-criticalCss'][] = '<link rel="stylesheet" type="text/css" href="' . $relPath . '" media="all" t3essentials-critical-css>';
            }
        }
    }

    /**
     * Moves JS Files specified in page.includeJS and page.includeJSLibs
     * to their footer counterpart.
     */
    private function moveJsToBottom() {
        $headScripts = $this->params['jsFiles'];
        $headLibs = $this->params['jsLibs'];

        $this->params['jsFiles'] = '';
        $this->params['jsLibs'] = '';
        $this->params['jsFooterFiles'] = $headScripts . $this->params['jsFooterFiles'];
        $this->params['jsFooterLibs'] = $headLibs . $this->params['jsFooterLibs'];
    }

    /**
     * Creates a new instance of JavascriptOptimizer with all parameters
     * Adds all scripts specified in config.includeJSFooter and replaces the jsFooterFiles with the concatenated file.
     */
    private function concatenateFooterJS() {
        $jsOptimizer = new JavascriptOptimizer();
        $jsOptimizer->addScripts($this->params['jsFooterFiles'], true);
        $optimizedScriptPath = reset($jsOptimizer->optimize())['file'];

        if (file_exists($optimizedScriptPath)) {
            $this->params['jsFooterFiles'] = '<script src="' . $optimizedScriptPath . '" type="text/javascript" async defer></script>';
        }
    }

    private function concatenateStyles() {
        $cssOptimizer = new CssOptimizer();
        $cssOptimizer->addStyles($this->params['cssLibs'] . $this->params['cssFiles'], true);
        $optimizedStylePath = reset($cssOptimizer->optimize())['file'];

        if (file_exists($optimizedStylePath)) {
            $deferredLoadingScript = $this->settings['performance']['css']['deferredLoadingScript'];
            $deferredLoadingScript = str_replace('$$CSS-FILE$$', $optimizedStylePath, $deferredLoadingScript);

            $this->footerData['10-deferedStyles'][] = '<script t3essentials-deferred-css>' . $deferredLoadingScript . '</script>';

            $this->params['cssLibs'] = '';
            $this->params['cssFiles'] = '';
        }
    }

    /**
     * Prepares all urls specified in typoscript settings for dns-prefetch
     * If the auto discover setting is enabled it will add the urls of the libraries in jsFooterLibs as a dns-prefetch
     * link as well.
     * Duplicate urls will be removed via array_unique
     */
    private function prepareDnsPrefetchLinks() {
        $urls = $this->settings['performance']['dnsPrefetch']['urls'];

        if ($this->settings['performance']['dnsPrefetch']['autoAddLibraries'] == '1') {
            $jsLibs = $this->params['jsFooterLibs'];
            $paths = SimpleHtmlParser::getAttributesFromTagsWithinHtmlString($jsLibs, 'script', 'src');

            foreach ($paths as $path) {
                $parts = parse_url($path);
                $urls[] = $parts['scheme'] . '://' . $parts['host'];
            }
        }

        foreach (array_unique($urls) as $url) {
            $this->addDnsPrefetchLink($url);
        }
    }

    /**
     * Adds $url as dns-prefetch link to header data
     *
     * @param $url
     */
    private function addDnsPrefetchLink($url) {
        $this->headerData['20-dnsPrefetch'][] = '<link rel="dns-prefetch" href="' . $url . '">';
    }

    /**
     * Gets the URL returned by the typolink_URL function and adds it as the canonical url for the current page.
     */
    private function setCanonicalUrl() {
        $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $pageUrl = $cObj->typolink_URL(['parameter' => $GLOBALS['TSFE']->id, 'forceAbsoluteUrl' => 1]);

        $this->headerData['10-canonicalUrl'][] = '<link rel="canonical" href="' . $pageUrl . '">';
    }

    /**
     * Gets the URL returned by the typolink_URL function for every language
     * and adds it as a hreflang-tag for the current page.
     */
    private function addHrefLangUrls() {
        if (is_array($this->settings['seo']['hrefLang']['languages'])) {
            $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

            foreach ($this->settings['seo']['hrefLang']['languages'] as $lang => $id) {
                $pageUrl = $cObj->typolink_URL([
                    'parameter' => $GLOBALS['TSFE']->id,
                    'additionalParams' => '&L=' . $id,
                    'forceAbsoluteUrl' => 1
                ]);

                $this->headerData['15-hrefLang'][] = '<link rel="alternate" hreflang="' . $lang . '" href="' . $pageUrl . '">';
            }
        }
    }

    /**
     * Bilds a query string for google fonts with all specified fonts in typoscript settings
     * Subsets are supported as well
     * Automatically adds a dns-prefetch link for the google fonts url
     */
    private function addGoogleFonts()
    {
        $fontsToAdd = $this->settings['services']['googleFonts']['fonts'];
        $fontQueryString = '';

        foreach ($fontsToAdd as $font) {
            if (!empty($fontQueryString)) {
                $fontQueryString .= '|';
            }
            $fontQueryString .= $font['name'] . ':' . str_replace(' ', '', $font['weights']);
        }

        if (isset($this->settings['services']['googleFonts']['subset'])) {
            $fontQueryString .= '&amp;subset=' . $this->settings['services']['googleFonts']['subset'];
        }

        if (!empty($fontQueryString)) {
            $googleFontsUrl = 'https://fonts.googleapis.com';

            $tag = '<link rel="stylesheet" type="text/css" href="' . $googleFontsUrl . '/css?family=' . $fontQueryString . '">';

            if ($this->settings['services']['googleFonts']['addAtBottom'] == '1') {
                $this->footerData['30-googleFonts'][] = $tag;
            } else {
                $this->headerData['30-googleFonts'][] = $tag;
            }

            $this->addDnsPrefetchLink($googleFontsUrl);
        }
    }

    /**
     * Adds Analytics script to head with the specified Tracing-ID only if set.
     */
    private function addAnalyticsScript() {
        $trackingID = $this->settings['services']['googleAnalytics']['trackingID'];
        $analyticsScript = str_replace('$$TRACKING-ID$$', $trackingID, trim($this->settings['services']['googleAnalytics']['trackingScript']));

        if ($trackingID !== 'UA-XXXXXX-XX') {
            $this->headerData['40-analytics'][] = $analyticsScript;
        }
    }

    /**
     * Adds the viewport meta tag to the header data
     */
    private function setViewportMetaTag() {
        $content = $this->settings['page']['viewport'];
        $this->headerData['05-meta'][] = '<meta name="viewport" content="' . $content . '">';
    }

    /**
     * Appends contents of headerData property to header data.
     *
     * @param $data
     */
    private function addHeaderData() {
        if (is_array($this->headerData)) {
            ksort($this->headerData);

            foreach ($this->headerData as $headerDataCategory) {
                foreach ($headerDataCategory as $headerDataEntry) {
                    $this->params['headerData'][1] .= "\r\n" . $headerDataEntry;
                }
            }
        }
    }

    /**
     * Appends contents of footerData property to footer data.
     *
     * @param $data
     */
    private function addFooterData() {
        if (is_array($this->footerData)) {
            ksort($this->footerData);

            foreach ($this->footerData as $footerDataCategory) {
                foreach ($footerDataCategory as $footerDataEntry) {
                    $this->params['footerData'][1] .= "\r\n" . $footerDataEntry;
                }
            }
        }
    }
}