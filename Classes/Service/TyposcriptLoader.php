<?php

namespace Sethorax\T3essentials\Service;

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
 * Class TyposcriptLoader
 * @package Sethorax\T3essentials\Service
 */
class TyposcriptLoader {
    /**
     * Returns typoscript settings for the given $_EXTKEY as an array without the dots.
     *
     * @return array
     */
    public static function getSetup($_EXTKEY, $plugin = true) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
        $settings = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        return self::removeDotsFromKeys($settings[$plugin ? 'plugin.' : 'config.'][$_EXTKEY . '.']);
    }


    /**
     * Creates an array without the dots from typoscript settings.
     * If $value is an array it will clean the dots as well.
     *
     * @param $settings
     * @return array
     */
    private static function removeDotsFromKeys($settings) {
        $conf = [];

        foreach ($settings as $key => $value) {
            $conf[self::removeDotFromString($key)] = is_array($value) ? self::removeDotsFromKeys($value) : $value;
        }

        return $conf;
    }

    /**
     * Removes a dot at the end of the given $string.
     *
     * @param $string
     * @return mixed
     */
    private static function removeDotFromString($string) {
        return preg_replace('/\.$/', '', $string);
    }
}