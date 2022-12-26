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
if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$translater = new dcTranslater();

if (!empty($_POST['save'])) {
    try {
        foreach ($translater->getDefaultSettings() as $key => $value) {
            $translater->$key = $_POST[$key] ?? '';
        }
        $translater->writeSettings();
        dcAdminNotices::addSuccessNotice(
            __('Configuration successfully updated.')
        );
        dcCore::app()->adminurl->redirect(
            'admin.plugins',
            ['module' => 'translater', 'conf' => 1, 'redir' => dcCore::app()->admin->__get('list')->getRedir()]
        );
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

echo '
<div class="fieldset"><h4>' . __('Translation') . '</h4>
<p><label for="write_langphp">' .
form::checkbox('write_langphp', '1', $translater->write_langphp) .
__('Write .lang.php files') . '</label></p>
<p><label for="scan_tpl">' .
form::checkbox('scan_tpl', '1', $translater->scan_tpl) .
__('Translate also strings of template files') . '</label></p>
<p><label for="parse_nodc">' .
form::checkbox('parse_nodc', '1', $translater->parse_nodc) .
__('Translate only unknow strings') . '</label></p>
<p><label for="hide_default">' .
form::checkbox('hide_default', '1', $translater->hide_default) .
__('Hide default modules of Dotclear') . '</label></p>
<p><label for="parse_comment">' .
form::checkbox('parse_comment', '1', $translater->parse_comment) .
__('Write comments in files') . '</label></p>
<p><label for="parse_user">' .
form::checkbox('parse_user', '1', $translater->parse_user) .
__('Write informations about author in files') . '</label></p>
<p><label for="parse_userinfo">' . __('User info:') . '</label>' .
form::field('parse_userinfo', 65, 255, $translater->parse_userinfo) . '</p>
<p class="form-note">' . sprintf(
    __('Following informations can be used: %s'),
    implode(', ', $translater::$allowed_user_informations)
) . '
</p>
</div>

<div class="fieldset"><h4>' . __('Import/Export') . '</h4>
<p><label for="import_overwrite">' .
form::checkbox('import_overwrite', '1', $translater->import_overwrite) .
__('Overwrite existing languages') . '</label></p>
<p><label for="export_filename">' . __('Name of exported package:') . '</label>' .
form::field('export_filename', 65, 255, $translater->export_filename) . '</p>
</div>

<div class="fieldset"><h4>' . __('Backups') . '</h4>
<p><label for="backup_auto">' .
form::checkbox('backup_auto', '1', $translater->backup_auto) .
__('Make backups when changes are made') . '</label></p>
<p><label for="backup_limit" class="classic">' . sprintf(
    __('Limit backups to %s files per module'),
    form::number('backup_limit', ['min' => 0, 'max' => 50, 'default' => $translater->backup_limit])
) . '</label></p>
<p class="form-note">' . __('Set to 0 for no limit.') . '</p>
<p><label for="backup_folder">' . __('Store backups in:') . '</label>' .
form::combo('backup_folder', $translater::$allowed_backup_folders, $translater->backup_folder) . '</p>
</div>

<div class="fieldset"><h4>' . __('Behaviors') . '</h4>
<p><label for="start_page">' . __('Default start menu:') . '</label>' .
form::combo('start_page', [
    __('Plugins') => 'plugin',
    __('Themes')  => 'theme',
    __('Home')    => '-',
], $translater->start_page) . '</p>
<p><label for="plugin_menu">' .
form::checkbox('plugin_menu', '1', $translater->plugin_menu) .
__('Enable menu on plugins page') . '</label></p>
<p><label for="theme_menu">' .
form::checkbox('theme_menu', '1', $translater->theme_menu) .
__('Enable menu on themes page') . '</label></p>
</div>';

dcPage::helpBlock('translater.config');
