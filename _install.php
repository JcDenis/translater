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

$rdc_version = '2.5-alpha';
$new_version = $core->plugins->moduleInfo('translater', 'version');
$old_version = $core->getVersion('translater');

if (version_compare($old_version, $new_version, '>=')) {
    return;
}

try {
    if (version_compare(str_replace("-r", "-p", DC_VERSION), $rdc_version, '<')) {
        throw new Exception(sprintf('%s requires Dotclear %s', 'translater', $rdc_version));
    }

    $core->blog->settings->addNamespace('translater');

    $core->blog->settings->translater->put('translater_plugin_menu', 0, 'boolean', 'Put a link in plugins page', false, true);
    $core->blog->settings->translater->put('translater_theme_menu', 0,'boolean', 'Put a link in themes page', false, true);
    $core->blog->settings->translater->put('translater_backup_auto', 1,'boolean', 'Make a backup of languages old files when there are modified', false, true);
    $core->blog->settings->translater->put('translater_backup_limit', 20,'string', 'Maximum backups per module', false, true);
    $core->blog->settings->translater->put('translater_backup_folder', 'module',' string', 'In which folder to store backups', false, true);
    $core->blog->settings->translater->put('translater_start_page', 'setting,', 'string', 'Page to start on', false, true);
    $core->blog->settings->translater->put('translater_write_po', 1, 'boolean', 'Write .po languages files', false, true);
    $core->blog->settings->translater->put('translater_write_langphp', 1, 'boolean', 'Write .lang.php languages files', false, true);
    $core->blog->settings->translater->put('translater_scan_tpl', 0, 'boolean', 'Translate strings of templates files', false, true);
    $core->blog->settings->translater->put('translater_parse_nodc', 1, 'boolean', 'Translate only untranslated strings of Dotclear', false, true);
    $core->blog->settings->translater->put('translater_hide_default', 1, 'boolean', 'Hide default modules of Dotclear', false, true);
    $core->blog->settings->translater->put('translater_parse_comment', 1, 'boolean', 'Write comments and strings informations in lang files', false, true);
    $core->blog->settings->translater->put('translater_parse_user', 1,'boolean', 'Write inforamtions about author in lang files', false, true);
    $core->blog->settings->translater->put('translater_parse_userinfo', 'displayname, email', 'string','Type of informations about user to write', false, true);
    $core->blog->settings->translater->put('translater_import_overwrite', 0, 'boolean', 'Overwrite existing languages when import packages', false, true);
    $core->blog->settings->translater->put('translater_export_filename', 'type-module-l10n-timestamp', 'string','Name of files of exported package', false, true);
    $core->blog->settings->translater->put('translater_proposal_tool', 'google', 'string', 'Id of default tool for proposed translation', false, true);
    $core->blog->settings->translater->put('translater_proposal_lang', 'en', 'string', 'Default source language for proposed translation', false, true);

    $core->setVersion('translater', $new_version);

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
return false;