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
    return null;
} 

$s = [
    ['translater_plugin_menu', 0, 'boolean', 'Put a link in plugins page'],
    ['translater_theme_menu', 0,'boolean', 'Put a link in themes page'],
    ['translater_backup_auto', 1,'boolean', 'Make a backup of languages old files when there are modified'],
    ['translater_backup_limit', 20,'string', 'Maximum backups per module'],
    ['translater_backup_folder', 'module',' string', 'In which folder to store backups'],
    ['translater_start_page', 'setting,', 'string', 'Page to start on'],
    ['translater_write_po', 1, 'boolean', 'Write .po languages files'],
    ['translater_write_langphp', 1, 'boolean', 'Write .lang.php languages files'],
    ['translater_scan_tpl', 0, 'boolean', 'Translate strings of templates files'],
    ['translater_parse_nodc', 1, 'boolean', 'Translate only untranslated strings of Dotclear'],
    ['translater_hide_default', 1, 'boolean', 'Hide default modules of Dotclear'],
    ['translater_parse_comment', 1, 'boolean', 'Write comments and strings informations in lang files'],
    ['translater_parse_user', 1,'boolean', 'Write inforamtions about author in lang files'],
    ['translater_parse_userinfo', 'displayname, email', 'string','Type of informations about user to write'],
    ['translater_import_overwrite', 0, 'boolean', 'Overwrite existing languages when import packages'],
    ['translater_export_filename', 'type-module-l10n-timestamp', 'string','Name of files of exported package'],
    ['translater_proposal_tool', 'google', 'string', 'Id of default tool for proposed translation'],
    ['translater_proposal_lang', 'en', 'string', 'Default source language for proposed translation']
];

try {
    if (version_compare($core->getVersion($id), $this->moduleInfo($id, 'version'), '>=')) {
        return null;
    }
    $core->blog->settings->addNamespace('translater');
    foreach($s as $v) {
        $core->blog->settings->translater->put($v[0], $v[1], $v[2], $v[3], false, true);
    }
    $core->setVersion($id, $this->moduleInfo($id, 'version'));
    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
return false;