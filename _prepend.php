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

$d = dirname(__FILE__) . '/inc/';

$__autoload['dcTranslater']                = $d . 'class.dc.translater.php';
$__autoload['dcTranslaterDefaultSettings'] = $d . 'class.dc.translater.php';
$__autoload['dcTranslaterModule']          = $d . 'class.dc.translater.module.php';
$__autoload['dcTranslaterLang']            = $d . 'class.dc.translater.lang.php';
$__autoload['translaterRest']              = $d . 'class.translater.rest.php';

if (isset($core->adminurl)) {
    $core->adminurl->register('translater', 'plugin.php', ['p' => 'translater']);
}
