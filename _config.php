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

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$redir = empty($_REQUEST['redir']) ? 
    $list->getURL() . '#plugins' : $_REQUEST['redir'];

# -- Get settings --
$core->blog->settings->addNamespace('translater');
$s = $core->blog->settings->translater;

$translater = new dcTranslater($core);

$combo_backup_limit = [
    5 => 5,
    10 => 10,
    15 => 15,
    20 => 20,
    40 => 40,
    60 => 60
];

$combo_backup_folder = [
    'module' => __('locales folders of each module'),
    'plugin' => __('plugins folder root'),
    'public' => __('public folder root'),
    'cache' => __('cache folder of Dotclear'),
    'translater' =>__('locales folder of translater')
];

$combo_start_page = [
    'modules_plugin' => __('Plugins'),
    'modules_theme' => __('Themes'),
    'pack' => __('Import/Export')
];

# -- Set settings --
if (!empty($_POST['save'])) {
        try {
            if (empty($_POST['translater_write_po'])
             && empty($_POST['translater_write_langphp'])) {
                throw new Exception('You must choose one file format at least');
            }
            foreach($translater->getDefaultSettings() as $k => $v) {
                $translater->set($k, (isset($_POST['translater_' . $k]) ? $_POST['translater_' . $k] : ''));
            }
            foreach($translater->proposal->getTools() AS $k => $v) {
                $v->save();
            }
            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            http::redirect(
                $list->getURL('module=translater&conf=1&redir=' .
                $list->getRedir())
            );
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
}

# -- Display form --
echo '
<div class="fieldset">
<h4>' . __('Translation') . '</h4>
<p><label class="classic">' . 
form::checkbox('translater_write_po', '1' ,$translater->write_po) . ' 
' . __('Write .po files') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_write_langphp', '1', $translater->write_langphp) . ' 
' . __('Write .lang.php files') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_scan_tpl', '1', $translater->scan_tpl) . ' 
' . __('Translate also strings of template files') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_parse_nodc', '1', $translater->parse_nodc) . ' 
' . __('Translate only unknow strings') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_hide_default', '1', $translater->hide_default) . ' 
' . __('Hide default modules of Dotclear') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_parse_comment', '1', $translater->parse_comment) . ' 
' . __('Write comments in files') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_parse_user', '1', $translater->parse_user) . ' 
' . __('Write informations about author in files') . '</label><br />
' . form::field('translater_parse_userinfo', 65, 255, $translater->parse_userinfo) . '</p>
</div>

<div class="fieldset">
<h4>' . __('Tools') . '</h4>
<p><label class="classic">' . __('Default language of l10n source:') . '<br />' . 
form::combo('translater_proposal_lang',
    array_flip($translater->getIsoCodes()), $translater->proposal_lang) . '</label></p>

<h4>' . __('Select and configure the tool to use to translate strings:') . '</h4>';

foreach($translater->proposal->getTools() AS $k => $v) {
    $form = $v->form();

    echo '
    <dd>
    <dt><label class="classic">' . 
    form::radio('translater_proposal_tool', $k, $k == $translater->proposal_tool) . ' 
    ' . $v->getDesc() . '</label></dt><dd>' . 
    (empty($form) ?
        '<p>' . sprintf(__('Nothing to configure for %s tool . '), $v->getName()) . '</p>' :
        $form
    ) . '</dd></dl>';
}

echo '
</div>

<div class="fieldset">
<h4>' . __('Import/Export') . '</h4>
<p><label class="classic">' . 
form::checkbox('translater_import_overwrite', '1', $translater->import_overwrite) . ' 
' . __('Overwrite existing languages') . '</label></p>
<p><label class="classic">' . __('Name of exported package') . '<br />
' . form::field('translater_export_filename', 65, 255, $translater->export_filename) . '</label></p>
</div>

<div class="fieldset">
<h4>' . __('Backups') . '</h4>
<p><label class="classic">' . 
form::checkbox('translater_backup_auto', '1', $translater->backup_auto) . ' 
' . __('Make backups when changes are made') . '</label></p>
<p><label class="classic">' . sprintf(__('Limit backups to %s files per module'),
form::combo('translater_backup_limit',
    array_flip($combo_backup_limit), $translater->backup_limit)) . '</label></p>
<p><label class="classic">' . sprintf(__('Store backups in %s'),
form::combo('translater_backup_folder',
    array_flip($combo_backup_folder), $translater->backup_folder)) . '</label></p>
</div>

<div class="fieldset">
<h4>' . __('Behaviors') . '</h4>
<p><label class="classic">' . __('Default start menu:') . '<br />' . 
form::combo('translater_start_page',
    array_flip($combo_start_page), $translater->start_page) . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_plugin_menu', '1', $translater->plugin_menu) . ' 
' . __('Enable menu on extensions page') . '</label></p>
<p><label class="classic">' . 
form::checkbox('translater_theme_menu', '1', $translater->theme_menu) . ' 
' . __('Enable menu on themes page') . '</label></p>
</div>
';