<?php

defined('TYPO3_MODE') or die();


/**
 * Add Extension TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( $_EXTKEY , 'Configuration/TypoScript' , 'T3 Essentials' );