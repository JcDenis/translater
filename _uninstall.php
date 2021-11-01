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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$this->addUserAction(
    /* type */ 
    'settings',
    /* action */ 
    'delete_all',
    /* ns */ 
    'translater',
    /* description */ 
    __('delete all settings')
);

$this->addUserAction(
    /* type */ 
    'plugins',
    /* action */ 
    'delete',
    /* ns */ 
    'translater',
    /* description */ 
    __('delete plugin files')
);

$this->addUserAction(
    /* type */ 
    'versions',
    /* action */ 
    'delete',
    /* ns */ 
    'translater',
    /* description */ 
    __('delete the version number')
);

$this->addDirectAction(
    /* type */ 
    'settings',
    /* action */ 
    'delete_all',
    /* ns */ 
    'translater',
    /* description */ 
    sprintf(__('delete all %s settings'), 'translater')
);

$this->addDirectAction(
    /* type */ 
    'plugins',
    /* action */ 
    'delete',
    /* ns */ 
    'translater',
    /* description */ 
    sprintf(__('delete %s plugin files'), 'translater')
);

$this->addDirectAction(
    /* type */ 
    'versions',
    /* action */ 
    'delete',
    /* ns */ 
    'translater',
    /* description */ 
    sprintf(__('delete %s version number'), 'translater')
);
