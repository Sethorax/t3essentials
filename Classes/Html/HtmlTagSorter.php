<?php

namespace Sethorax\T3essentials\Html;

use Sethorax\T3essentials\Utility\ArrayUtility;

class HtmlTagSorter {
    public $doc;

    public $targetNode;

    public function __construct($html) {
        if (strpos($html, '<!DOCTYPE') !== false) {
            $this->doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $this->doc->loadHTML($html);
        } else {
            $this->doc = null;
        }
    }

    public function setTargetNode($nodeName) {
        if ($this->doc !== null) {
            $this->targetNode = $this->doc->getElementsByTagName($nodeName)[0];
        }

        return $this;
    }

    public function sort() {
        if ($this->doc !== null) {
            if ($this->targetNode->childNodes->length !== null) {
                $nodesToSort = [];

                for ($i = $this->targetNode->childNodes->length; $i >= 0; $i--) {
                    $childNode = $this->targetNode->childNodes->item($i);

                    if (isset($childNode)) {
                        if ($childNode->nodeName == '#comment') {
                            $nodesToSort['comment'][] = $childNode;
                        } else {
                            if ($childNode->nodeName == 'link') {
                                switch ($childNode->getAttribute('rel')) {
                                    case 'dns-prefetch':
                                        $nodesToSort['link']['10-dns-prefetch'][] = $childNode;
                                        break;

                                    case 'canonical':
                                        $nodesToSort['link']['20-canonical'][] = $childNode;
                                        break;

                                    case 'alternate':
                                        $nodesToSort['link']['30-alternate'][] = $childNode;
                                        break;

                                    case 'stylesheet':
                                        $nodesToSort['link']['40-stylesheet'][] = $childNode;
                                        break;

                                    default:
                                        $nodesToSort['link']['90-default'][] = $childNode;
                                        break;
                                }
                            } else if ($childNode->nodeName == 'meta') {
                                if (!empty($childNode->getAttribute('charset'))) {
                                    $nodesToSort['meta']['10-charset'][] = $childNode;
                                } else {
                                    $nodesToSort['meta']['90-default'][] = $childNode;
                                }
                            } else {
                                $nodesToSort[$childNode->tagName][] = $childNode;
                            }
                        }

                        $childNode->parentNode->removeChild($childNode);
                    }
                }

                ksort($nodesToSort['meta']);
                ksort($nodesToSort['link']);

                $sortedNodeList = $this->sortByKeys([
                    1 => [
                        'key' => 'title'
                    ],
                    2 => [
                        'key' => 'meta',
                        'assoc' => true
                    ],
                    3 => [
                        'key' => 'base'
                    ],
                    4 => [
                        'key' => 'link',
                        'assoc' => true
                    ],
                    5 => [
                        'key' => 'style'
                    ],
                    6 => [
                        'key' => 'comment'
                    ],
                ], $nodesToSort);

                foreach ($sortedNodeList as $node) {
                    $this->targetNode->appendChild($node);
                }
            }
        }
    }

    public function getHtml() {
        if ($this->doc !== null) {
            return $this->doc->saveHTML();
        } else {
            return '';
        }
    }

    private function sortByKeys($keys, $nodesToSort) {
        $sortedNodes = [];
        $keysToSort = array_keys($nodesToSort);

        foreach ($keys as $key) {
            $sortedNodes = ArrayUtility::mergeArrays($sortedNodes, $nodesToSort[$key['key']], isset($key['assoc']) ? $key['assoc'] : false);
            $keysToSort = ArrayUtility::unsetByValue($keysToSort, $key['key']);
        }

        foreach ($keysToSort as $key) {
            $sortedNodes = ArrayUtility::mergeArrays($sortedNodes, $nodesToSort[$key]);
        }

        return $sortedNodes;
    }
}