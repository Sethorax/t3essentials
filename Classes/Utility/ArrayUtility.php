<?php

namespace Sethorax\T3essentials\Utility;

class ArrayUtility {
    public static function mergeArrays($target, $source, $isAssoc = false) {
        if (is_array($source)) {
            if ($isAssoc) {
                foreach ($source as $array) {
                    $target = array_merge($target, $array);
                }
            } else {
                $target = array_merge($target, $source);
            }
        }

        return $target;
    }

    public static function unsetByValue($array, $targetValue) {
        foreach ($array as $key => $value) {
            if ($value == $targetValue) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}