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

$d = __DIR__ . '/inc/';

Clearbricks::lib()->autoload(['dcTranslater' => $d . 'class.dc.translater.php']);
Clearbricks::lib()->autoload(['dcTranslaterDefaultSettings' => $d . 'class.dc.translater.php']);
Clearbricks::lib()->autoload(['dcTranslaterModule' => $d . 'class.dc.translater.module.php']);
Clearbricks::lib()->autoload(['dcTranslaterLang' => $d . 'class.dc.translater.lang.php']);
Clearbricks::lib()->autoload(['translaterRest' => $d . 'class.translater.rest.php']);

if (isset(dcCore::app()->adminurl)) {
    dcCore::app()->adminurl->register('translater', 'plugin.php', ['p' => 'translater']);
}
