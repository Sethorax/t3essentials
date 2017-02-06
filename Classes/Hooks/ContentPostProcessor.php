<?php

namespace Sethorax\T3essentials\Hooks;

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

use Sethorax\T3essentials\Html\HtmlMinfier;
use Sethorax\T3essentials\Html\HtmlTagSorter;
use Sethorax\T3essentials\Service\TyposcriptLoader;

class ContentPostProcessor {
    protected $params;

    protected $settings;

    /**
     * Render method when page is being generated.
     *
     * @return void
     */
    public function process($params) {
        if (TYPO3_MODE == 'FE') {
            $this->params = $params;
            $this->settings = TyposcriptLoader::getSetup('t3essentials', false);

            $this->checkTyposcriptSettings();

            return $this->params;
        } else {
            return $params;
        }
    }

    public function checkTyposcriptSettings() {
        if ($this->settings['page']['sortHeadTags'] == '1') {
            $this->sortHeadTags();
        }

        if ($this->settings['performance']['html']['minify'] == '1') {
            $this->minifyHtml();
        }
    }


    private function sortHeadTags() {
        $content = $this->params['pObj']->content;

        if (strpos($content, '<!DOCTYPE') !== false) {
            $sorter = new HtmlTagSorter($content);
            $sorter->setTargetNode('head')
                ->sort();

            $this->params['pObj']->content = $sorter->getHtml();
        }
    }

    private function minifyHtml() {
        $this->params['pObj']->content = HtmlMinfier::minify($this->params['pObj']->content);
    }
}