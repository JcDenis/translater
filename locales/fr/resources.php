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

foreach (['index', 'type', 'module', 'lang', 'config'] as $v) {
    $__resources['help']['translater.' . $v] = dirname(__FILE__) . '/help/translater.' . $v . '.html';
}
