<?php
/**
 * @brief translater, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis & contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return;
}

Clearbricks::lib()->autoload([
    'dcTranslater'                => __DIR__ . '/inc/class.dc.translater.php',
    'dcTranslaterDefaultSettings' => __DIR__ . '/inc/class.dc.translater.php',
    'dcTranslaterModule'          => __DIR__ . '/inc/class.dc.translater.module.php',
    'dcTranslaterLang'            => __DIR__ . '/inc/class.dc.translater.lang.php',
    'translaterRest'              => __DIR__ . '/class.translater.rest.php',
]);

if (isset(dcCore::app()->adminurl)) {
    dcCore::app()->adminurl->register('translater', 'plugin.php', ['p' => 'translater']);
}
