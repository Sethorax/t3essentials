<?php

namespace Sethorax\T3essentials\Html;

class HtmlMinfier {
    protected $html;


    public function __construct($html) {
        $this->html = str_replace("\r\n", "\n", trim($html));
    }


    public function process() {
        return  preg_replace(
            '%(?>[^\S ]\s*| \s{2,})(?=(?:(?:[^<]++| <(?!/?(?:textarea|pre)\b))*+)(?:<(?>textarea|pre)\b| \z))%ix',
            ' ',
            $this->html
        );
    }


    public static function minify($html) {
        $instance = new self($html);
        return $instance->process();
    }
}