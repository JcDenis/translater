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

$this->registerModule(
    'Translater',
    'Translate your Dotclear plugins and themes',
    'Jean-Christian Denis & contributors',
    '2022.12.26',
    [
        'requires'    => [['core', '2.24']],
        'permissions' => null,
        'type'        => 'plugin',
        'support'     => 'http://forum.dotclear.org/viewtopic.php?id=39220',
        'details'     => 'https://plugins.dotaddict.org/dc2/details/' . basename(__DIR__),
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . basename(__DIR__) . '/master/dcstore.xml',
    ]
);
