<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of translater, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$core->blog->settings->addNamespace('translater');
$core->addBehavior('pluginsToolsTabs', ['translaterAdminBehaviors', 'pluginsToolsTabs']);
$core->addBehavior('adminCurrentThemeDetails', ['translaterAdminBehaviors', 'adminCurrentThemeDetails']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addGoogleProposalTool']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addYahooProposalTool']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addMicrosoftProposalTool']);
$core->rest->addFunction('getProposal', ['translaterRest', 'getProposal']);

$_menu['Plugins']->addItem(
    __('Translater'),
    'plugin.php?p=translater',
    'index.php?pf=translater/icon.png',
    preg_match('/plugin.php\?p=translater(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->isSuperAdmin()
);

class translaterAdminBehaviors
{
    # Plugins tab
    public static function pluginsToolsTabs($core)
    {
        if (!$core->blog->settings->translater->translater_plugin_menu || !$core->auth->isSuperAdmin()) {
            return;
        }
        
        echo 
        '<div class="multi-part" id="translater" title="' .
        __('Translate plugins') .
        '">' .
        '<table class="clear"><tr>' .
        '<th>&nbsp;</th>' .
        '<th>' . __('Name') . '</th>' .
        '<th class="nowrap">' . __('Version') . '</th>' .
        '<th class="nowrap">' . __('Details') . '</th>' .
        '<th class="nowrap">' . __('Author') . '</th>' .
        '</tr>';
        
        $modules = $core->plugins->getModules();
        
        foreach ($modules as $name => $plugin) {
            echo
            '<tr class="line">' .
            '<td class="nowrap">' .
            '<a href="plugin.php?p=translater&amp;part=module&amp;type=plugin&amp;module=' . $name . '"' .
            ' title="' . __('Translate this plugin') . '">' . __($plugin['name']) . '</a></td>' .
            '<td class="nowrap">' . $name . '</td>' .
            '<td class="nowrap">' . $plugin['version'] . '</td>' .
            '<td class="maximal">' . $plugin['desc'] . '</td>' .
            '<td class="nowrap">' . $plugin['author'] . '</td>' .
            '</tr>';
        }
        echo 
        '</table></div>';
    }
    
    # Themes menu
    public static function adminCurrentThemeDetails($core, $id, $infos)
    {
        if (!$core->blog->settings->translater->translater_theme_menu || !$core->auth->isSuperAdmin()) {
            return;
        }
        
        $root = path::real($infos['root']);
        
        if ($id != 'default' && is_dir($root.'/locales')) {
            return 
            '<p><a href="plugin.php?p=translater&amp;part=module&amp;type=theme&amp;module=' . $id . '"' .
            ' class="button">' . __('Translate this theme') . '</a></p>';
        }
    }
    
    # Google Translater tools
    public static function addGoogleProposalTool($proposal)
    {
        $proposal->addTool('googleProposalTool');
    }
    
    # Yahoo Babelfish tools
    public static function addYahooProposalTool($proposal)
    {
        $proposal->addTool('yahooProposalTool');
    }
    
    # Microsoft Bing tools
    public static function addMicrosoftProposalTool($proposal)
    {
        $proposal->addTool('microsoftProposalTool');
    }
}

$core->addBehavior('adminDashboardFavorites', 'translaterDashboardFavorites');

function translaterDashboardFavorites($core, $favs)
{
    $favs->register('translater', [
        'title' => __('Translater'),
        'url' => 'plugin.php?p=translater',
        'small-icon' => 'index.php?pf=translater/icon.png',
        'large-icon' => 'index.php?pf=translater/icon-big.png',
        'permissions' => 'usage,contentadmin'
    ]);
}