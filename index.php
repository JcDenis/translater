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

dcPage::checkSuper();

$translater = new dcTranslater($core);

$type   = $_REQUEST['type'] ?? $translater->start_page ?: '';
$module = $_REQUEST['module'] ?? '';
$lang   = $_REQUEST['lang'] ?? '';
$action = $_POST['action'] ?? '';

if (!in_array($type, ['plugin', 'theme'])) {
    $type = '';
}

if (!empty($type) && !empty($module)) {
    try {
        $module = $translater->getModule($type, $module);
    } catch(Exception $e) {
        $core->error->add($e->getMessage());
        $module = '';
    }
}

if (!empty($module) && !empty($lang)) {
    try {
        $lang = $translater->getLang($module, $lang);
    } catch(Exception $e) {
        $core->error->add($e->getMessage());
        $lang = '';
    }
}

$breadcrumb = [__('Translater') => $core->adminurl->get('translater', ['type' => '-'])];
if (empty($type)) {
    $breadcrumb = [__('Translater') => ''];
} elseif (empty($module)) {
    $breadcrumb[$type == 'plugin' ? __('Plugins') : __('Themes')] = '';
} elseif (empty($lang)) {
    $breadcrumb[$type == 'plugin' ? __('Plugins') : __('Themes')] = $core->adminurl->get('translater', ['type' => $type]);
    $breadcrumb[html::escapeHTML($module->name)] = '';
} elseif (!empty($lang)) {
    $breadcrumb[$type == 'plugin' ? __('Plugins') : __('Themes')] = $core->adminurl->get('translater', ['type' => $type]);
    $breadcrumb[html::escapeHTML($module->name)] = $core->adminurl->get('translater', ['type' => $type, 'module' => $module->id]);
    $breadcrumb[html::escapeHTML(sprintf(__('%s language edition'), $lang->name))] = '';
}

try {
    if ($action == 'module_create_backups') {
        if (empty($module) || empty($_POST['codes'])) {
            throw new Exception(__('Nothing to backup'));
        }
        $module_codes = $module->getUsedlangs();
        foreach($module_codes as $code_id) {
            if (in_array($code_id, $_POST['codes'])) {
                $module->createBackup($code_id);
            }
        }
        dcPage::addSuccessNotice(__('Backup successfully created'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id]);
    }

    if ($action == 'module_restore_backup') {
        if (empty($module) || empty($_POST['files'])) {
            throw New Exception(__('Nothing to restore'));
        }
        $module_backups = $module->getBackups(true);
        foreach($module_backups as $backup_file) {
            if (in_array($backup_file, $_POST['files'])) {
                $module->restoreBackup($backup_file);
            }
        }
        dcPage::addSuccessNotice(__('Backup successfully restored'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id]);
    }

    if ($action == 'module_delete_backup') {
        if (empty($module) || empty($_POST['files'])) {
            throw New Exception(__('Nothing to delete'));
        }
        $module_backups = $module->getBackups(true);
        foreach($module_backups as $backup_file) {
            if (in_array($backup_file, $_POST['files'])) {
                $module->deleteBackup($backup_file);
            }
        }
        dcPage::addSuccessNotice(__('Backup successfully deleted'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id]);
    }

    if ($action == 'module_export_pack') {
        if (empty($module) || empty($_POST['codes'])) {
            throw new Exception(__('Nothing to export'));
        }
        $module->exportPack($_POST['codes']);

        dcPage::addSuccessNotice(__('Language successfully exported'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id]);
    }

    if ($action == 'module_import_pack') {
        if (empty($_FILES['packfile']['name'])) {
            throw new Exception(__('Nothing to import'));
        }
        $module->importPack($_FILES['packfile']);

        dcPage::addSuccessNotice(__('Language successfully imported'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id]);
    }

    if ($action == 'module_add_code') {
        if (empty($module) || empty($_POST['code'])) {
            throw new Exception(__('Nothing to create'));
        }
        $module->addLang($_POST['code'], $_POST['from'] ?? '');

        dcPage::addSuccessNotice(__('Language successfully added'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id, 'lang' => $_POST['code']]);
    }

    if ($action == 'module_delete_codes') {
        if (empty($module) || empty($_POST['codes'])) {
            throw new Exception(__('Nothing to delete'));
        }
        $module_codes = $module->getUsedlangs();
        foreach($module_codes as $code_id) {
            if (in_array($code_id, $_POST['codes'])) {
                $module->delLang($code_id);
            }
        }
        dcPage::addSuccessNotice(__('Language successfully deleted'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id, 'lang' => $_POST['code']]);
    }

    if ($action == 'module_update_code') {
        if (empty($module) || empty($_POST['code']) || empty($_POST['entries'])) {
            throw new Exception(__('Nothing to update'));
        }
        if (!empty($_POST['update_group'])) {
            foreach($_POST['entries'] as $i => $entry) {
                if (isset($entry['check']) && isset($_POST['multigroup'])) {
                    $_POST['entries'][$i]['group'] = $_POST['multigroup'];
                }
            }
        }
        $module->updLang($_POST['code'], $_POST['entries']);

        dcPage::addSuccessNotice(__('Language successfully updated'));
        $core->adminurl->redirect('translater', ['type' => $type, 'module' => $module->id, 'lang' => $_POST['code']]);

    }

} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

echo 
'<html><head><title>' . __('Translater') . '</title>' . 
dcPage::jsPageTabs() . 
dcPage::cssLoad(dcPage::getPF('translater/css/translater.css')) .
dcpage::jsJson('translater', [
    'title_add_detail' => __('Use this text'),
    'image_field'      => dcPage::getPF('translater/img/field.png'),
    'image_toggle'     => dcPage::getPF('translater/img/toggle.png')
]) .
dcPage::jsLoad(dcPage::getPF('translater/js/translater.js')) .

# --BEHAVIOR-- translaterAdminHeaders
$core->callBehavior('translaterAdminHeaders') .

'</head><body>' .

dcPage::breadcrumb($breadcrumb) .
dcPage::notices();

if (empty($module) && $type != '') {
    // modules list
    echo '<form id="theme-form" method="post" action="' . $core->adminurl->get('translater', ['type' => 'plugin']) . '">';

    $res = '';
    $modules = $translater->getModules($type);
    ksort($modules);
    foreach ($modules as $module) {
        if ($translater->hide_default && in_array($module->id, $translater::$default_distrib_modules[$type])) {
            continue;
        }
        if ($module->root_writable) {
            $res .= sprintf(
                '<tr class="line"><td class="nowrap minimal"><a href="%s" title="%s">%s</a></td>',
                $core->adminurl->get('translater', ['type' => $module->type, 'module' => $module->id]),
                html::escapeHTML(sprintf(__('Translate module %s'), __($module->name))),
                html::escapeHTML(__($module->id))
            );
        } else {
            $res .= sprintf(
                '<tr class="line offline"><td class="nowrap">%s</td>',
                html::escapeHTML(__($module->id))
            );
        }
        $codes = $module->getLangs();
        foreach ($codes as $code_id => $code_name) {
            if ($module->root_writable) {
                $codes[$code_id] = sprintf(
                    '<a class="wait maximal nowrap" title="%s" href="%s">%s (%s)</a>',
                    html::escapeHTML(sprintf(__('Edit language %s of module %s'), html::escapeHTML($code_name), __($module->name))), 
                    $core->adminurl->get('translater', ['type' => $module->type, 'module' => $module->id, 'lang' => $code_id]),
                    html::escapeHTML($code_name),
                    $code_id
                );
            } else {
                $codes[$code_id] = html::escapeHTML($code_name) . '(' .$code_id . ')';
            }
        }
        $res .= sprintf(
            '<td class="nowrap maximal">%s</td><td class="nowrap minimal">%s</td><td class="nowrap minimal count">%s</td></tr>',
            implode(', ', $codes),
            html::escapeHTML($module->name),
            $module->version
        );
    }
    if ($res) {
        echo '
        <div class="table-outer">
        <table class="clear">
        <caption>' . sprintf(__('Modules list of type "%s"'), $type) .'</caption>
        <tr>
        <th class="nowrap">' . __('Id') . '</th>
        <th class="nowrap">' . __('Languages') . '</th>
        <th class="nowrap">' . __('Name') . '</th>
        <th class="nowrap">' . __('Version') . '</th>
        </tr>' .
        $res .
        '</table></div>';

    } else {
        echo '<tr><td colspan="6">' . __('There is no editable modules') . '</td></tr>';
    }
    echo '</form>';

    dcPage::helpBlock('translater.type');

} elseif (!empty($module) && empty($lang)) {
    $codes = $module->getUsedLangs();
    $backups = $module->getBackups();
    $unused_codes = $module->getUnusedLangs();

    // module summary   
    echo '<h3>' . sprintf(__('Module %s %s by %s'), $module->name, $module->version, $module->author) . '</h3>
    <ul class="nice col">
    <li><strong>' . __('Root') . '</strong> ' . $module->root . '</li>
    <li><strong>' . __('Locales') . '</strong> ' . $module->locales . '</li>
    <li><strong>' . __('Backups') . '</strong> ' . $module->getBackupRoot() . '</li>
    </ul>
    <p>&nbsp;</p>';

    // existing languages
    if (count($codes)) {
        echo 
        '<div class="clear fieldset"><h3>' . __('Translations') . '</h3>' . 
        '<form id="module-translations-form" method="post" action="' . $core->adminurl->get('translater') . '">' .
        '<table class="clear maximal">' . 
        '<caption>' . __('Existing languages translations') .'</caption>' .
        '<tr>' . 
        '<th class="nowrap" colspan="2">' . __('Language') . '</th>' . 
        '<th class="nowrap">' . __('Code') . '</th>' . 
        '<th class="nowrap">' . __('Backups') . '</th>' . 
        '<th class="nowrap">' . __('Last backup') . '</th>' . 
        '</tr>';

        foreach($codes AS $code_name => $code_id) {
            echo 
            '<tr class="line">' . 
            '<td class="minimal">' . form::checkbox(['codes[]', 'existing_code_' . $code_id], $code_id, '', '', '', false) . '</td>' .
            '<td class="nowrap">' . 
            '<a href="' . 
                $core->adminurl->get('translater', ['type' => $module->type, 'module' => $module->id, 'lang' => $code_id])
                 . '" title="' . sprintf(__('Edit %s language'), html::escapeHTML($code_name)) . '">' . $code_name . '</a>' . 
            '</td>' . 
            '<td class="nowrap maximal"> ' . $code_id . '</td>';

            if (isset($backups[$code_id])) {
                foreach($backups[$code_id] AS $file => $info) {
                    $time[$code_id] = isset($time[$code_id]) && $time[$code_id] > $info['time'] ? 
                        $time[$code_id] : $info['time'];
                }
                echo 
                '<td class="nowrap">' . count($backups[$code_id]) . '</td>' . 
                '<td class="nowrap"> ' . 
                dt::str('%Y-%m-%d %H:%M', $time[$code_id], $core->blog->settings->system->blog_timezone) . 
                '</td>';
            } else {
                echo '<td class="nowrap">' . __('no backups') . '</td><td class="maximal nowrap">-</td>';
            }
            echo '</tr>';
        }
        echo '</table>
        <div class="two-cols">
        <p class="col checkboxes-helpers"></p>

        <p class="col right">' . __('Selected languages action:') . ' ' . 
        form::combo('action', [
            __('Backup languages') => 'module_create_backups',
            __('Delete languages') => 'module_delete_codes',
            __('Export languages') => 'module_export_pack'
        ]) . ' 
        <input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
        $core->formNonce() . 
        $core->adminurl->getHiddenFormFields(
            'translater',
            ['type' => $module->type, 'module' => $module->id]
        ) . '
        </p></div></form><p>&nbsp;</p></div>';
    }

    // backups
    if (!empty($codes) || !empty($backups)) {
        // delete / retore backups
        if (!empty($backups)) {
            echo '<div class="fieldset"><h3>' . __('Backups') . '</h3>' . 
            '<form id="module-backups-form" method="post" action="' . $core->adminurl->get('translater') . '">' . 
            '<table class="clear">' . 
            '<caption>' . __('Existing languages backups') . '</caption>' .
            '<tr>' . 
            '<th class="nowrap" colspan="2">' . __('Language') . '</th>' . 
            '<th class="nowrap">' . __('Code') . '</th>' . 
            '<th class="nowrap">' . __('Date') . '</th>' . 
            '<th class="nowrap">' . __('File') . '</th>' . 
            '<th class="nowrap">' . __('Size') . '</th>' . 
            '</tr>';

            $table_line = 
                '<tr class="line">' .
                '<td class="minimal">%s</td>' .
                '<td class="nowrap"><label for="%s">%s</label></td>' .
                '<td class="nowrap maximal">%s</td>' .
                '<td class="nowrap count">%s</td>' .
                '<td class="nowrap">%s</td>' . 
                '<td class="nowrap count">%s</td>' .
                '</tr>';

            $i=0;
            foreach($backups as $backup_codes) {
                foreach($backup_codes as $backup_file => $backup_code) {
                    $i++;
                    $form_id = 'form_file_' . $backup_code['code'] . $backup_code['time'];
                    echo sprintf($table_line,
                        form::checkbox(['files[]', $form_id], $backup_file, '', '', '', false),
                        $form_id, $backup_code['name'],
                        $backup_code['code'],
                        dt::str(
                            $core->blog->settings->system->date_format . ' ' . $core->blog->settings->system->time_format, 
                            $backup_code['time'], 
                            $core->blog->settings->system->blog_timezone
                        ),
                        $backup_code['path']['basename'],
                        files::size($backup_code['size'])
                    );
                }
            }
            echo '
            </table>
            <div class="two-cols">
            <p class="col checkboxes-helpers"></p>

            <p class="col right">' . __('Selected backups action:') . ' ' . 
            form::combo('action', [
                __('Restore backups') => 'module_restore_backup',
                __('Delete backups') => 'module_delete_backup'
            ]) . '
            <input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            $core->formNonce() . 
            $core->adminurl->getHiddenFormFields(
                'translater',
                ['type' => $module->type, 'module' => $module->id]
            ) . '
            </p></div></form><p>&nbsp;</p></div>';
        }
    }

    echo '<div class="two-cols">';

    // add language
    if (!empty($unused_codes)) {
        echo '<div class="col fieldset"><h3>' . __('Add language') . '</h3>
        <form id="muodule-code-create-form" method="post" action="' . $core->adminurl->get('translater') . '">
        <p class="field"><label for="code">' . __('Select language:') . '</label>' .  
        form::combo(['code'], array_merge(['-' => '-'], $unused_codes), $core->auth->getInfo('user_lang')) . '</p>';
        if (empty($codes)) {
            echo '<p>' . form::hidden(['from'], '') . '</p>';
        } else {
            echo 
            '<p class="field"><label for="from">' . __('Copy from language:') . '</label>' .  
            form::combo(['from'], array_merge(['-' => ''], $codes)) . ' (' . __('optionnal') . ')</p>';
        }
        echo '
        <p><input type="submit" name="save" value="' . __('Create') . '" />' . 
        $core->formNonce() . 
        $core->adminurl->getHiddenFormFields(
            'translater',
            ['type' => $module->type, 'module' => $module->id, 'action' => 'module_add_code']
        ) . '
        </p></form><p>&nbsp;</p></div>';
    }

    // Import
    echo '<div class="col fieldset"><h3>' . __('Import') . '</h3>
    <form id="module-pack-import-form" method="post" action="' . $core->adminurl->get('translater') . '" enctype="multipart/form-data">
    <p><label for="packfile">' . __('Select languages package to import:') . '<label> ' .
    '<input id="packfile" type="file" name="packfile" /></p>
    <p>
    <input type="submit" name="save" value="' . __('Import') . '" />' . 
    $core->formNonce() . 
    $core->adminurl->getHiddenFormFields(
        'translater',
        ['type' => $module->type, 'module' => $module->id, 'action' => 'module_import_pack']
    ) . '
    </p></form><p>&nbsp;</p></div>';

    echo '</div>';

    dcPage::helpBlock('translater.module');

} elseif (!empty($lang)) {
    $lines = $lang->getMessages();

    echo 
    '<div id="lang-form">' . 
    '<form id="lang-edit-form" method="post" action="' . $core->adminurl->get('translater') . '">' . 
    '<table class="table-outer">' . 
    '<caption>' . sprintf(__('List of %s localized strings'), count($lines)) . '</caption>' .
    '<tr>' . 
    '<th colspan="2">' . __('Group') . '</th>' . 
    '<th>' . __('String') . '</th>' . 
    '<th>' . __('Translation') . '</th>' . 
    '<th>' . __('Existing') . '</th>' . 
    '<th>' . __('File') . '</th>' . 
    '</tr>';

    $table_line = 
        '<tr class="line%s">' .
        '<td class="nowrap minimal">%s</td>' .
        '<td class="nowrap minimal">%s</td>' .
        '<td>%s</td>' .
        '<td class="nowrap translatertarget">%s</td>' .
        '<td class="translatermsgstr">%s</td>' .
        '<td class="nowrap translatermsgfile">%s</td>' .
        '</tr>';
    $table_ul = '<div class="subtranslater"><strong>%s</strong><div class="strlist">%s</div></div><br />';
    $table_li = '<i>%s</i><br />';

    $i = 1;
    foreach ($lines AS $msgid => $rs) {
        $in_dc = ($rs['in_dc'] && $translater->parse_nodc);
        $allowed_l10n_groups = array_combine($translater::$allowed_l10n_groups, $translater::$allowed_l10n_groups);
        $t_msgstr = $t_files = $strin = [];

        foreach($rs['o_msgstrs'] as $o_msgstr) {
            if (!isset($strin[$o_msgstr['msgstr'][0]])) {
                $strin[$o_msgstr['msgstr'][0]] = [];
            }
            $strin[$o_msgstr['msgstr'][0]][] = ['module' => $o_msgstr['module'], 'file' => $o_msgstr['file']];
        }
        foreach($strin as $k => $v) {
            $res = [];
            foreach($v as $str) {
                $res[] = sprintf($table_li, html::escapeHTML($str['module'] . ':' . $str['file']));
            }
            $t_msgstr[] = sprintf($table_ul, html::escapeHTML($k), implode('', $res));
        }

        if (!empty($rs['files'][0])) {
            if (count($rs['files']) == 1) {
                $t_files[] = $rs['files'][0][0] . ':' . $rs['files'][0][1];
            } else {
                $res = [];
                foreach($rs['files'] as $location) {
                    $res[] = sprintf($table_li, implode(' : ', $location));
                }
                $t_files[] = sprintf($table_ul, sprintf(__('%s occurrences'), count($rs['files'])), implode('', $res));;
            }
        }

        echo sprintf($table_line, $in_dc ? ' offline' : ' translaterline',
            form::checkbox(['entries[' . $i . '][check]'], 1),
            form::combo(['entries[' . $i . '][group]'], $allowed_l10n_groups, $rs['group'], '', '', $in_dc),
            html::escapeHTML($msgid),
            form::hidden(['entries[' . $i . '][msgid]'], html::escapeHTML($msgid)) . 
            form::field(['entries[' . $i . '][msgstr][0]'], 48, 255, html::escapeHTML($rs['msgstr'][0]), '', '', $in_dc),
            implode('', $t_msgstr),
            implode('', $t_files)
        );

        if (!empty($rs['plural'])) {
            $t_msgstr = $strin = [];
            foreach($lang->plural as $j => $plural) {
                foreach($rs['o_msgstrs'] as $o_msgstr) {
                    if (isset($o_msgstr['msgstr'][$j+1])) {
                        if (!isset($strin[$o_msgstr['msgstr'][$j+1]])) {
                            $strin[$o_msgstr['msgstr'][$j+1]] = [];
                        }
                        $strin[$o_msgstr['msgstr'][$j+1]][] = ['module' => $o_msgstr['module'], 'file' => $o_msgstr['file']];
                    }
                }
                foreach($strin as $k => $v) {
                    $res = [];
                    foreach($v as $str) {
                        $res[] = sprintf($table_li, html::escapeHTML($str['module'] . ':' . $str['file']));
                    }
                    $t_msgstr[] = sprintf($table_ul, html::escapeHTML($k), implode('', $res));
                }

                echo sprintf($table_line, $in_dc ? ' offline' : ' translaterline',
                    '+',
                    sprintf(__('Plural "%s"'),  $plural),
                    sprintf(__('Plural form of "%s"'), $rs['plural']), 
                    form::hidden(['entries[' . $i . '][msgid_plural]'], html::escapeHTML($rs['plural'])) . 
                    form::field(['entries[' . $i . '][msgstr][' . $j+1 . ']'], 48, 255, html::escapeHTML($rs['msgstr'][$j+1] ?? ''), '', '', $in_dc),
                    implode('', $t_msgstr),
                    ''
                );
            }
        }
        $i++;
    }
    echo sprintf($table_line, ' offline',
        form::checkbox(['entries[' . $i . '][check]'], 1),
        form::combo(['entries[' . $i . '][group]'], $allowed_l10n_groups, 'main'),
        form::field(['entries[' . $i . '][msgid]'], 48, 255, ''),
        form::field(['entries[' . $i . '][msgstr][0]'], 48, 255, ''),
        '',
        ''
    );
    echo 
    '</table>' . 

    '<div class="two-cols">' .
    '<div class="col left">' .
    '<p class="checkboxes-helpers"></p>' .
    '<p><label for="update_group">' .
    form::checkbox('update_group', 1) .
    __('Change the group of the selected translations to:') . ' ' . 
    form::combo('multigroup', $allowed_l10n_groups) . '</label></p>' .
    '</div>' .
    '<p class="col right">' .
    '<input id="do-action" type="submit" value="' . __('Save') . ' (s)" accesskey="s" /></p>' .
    $core->formNonce() . 
    form::hidden(['code'], $lang->code) .
    $core->adminurl->getHiddenFormFields(
        'translater',
        ['type' => $module->type, 'module' => $module->id, 'lang' => $lang->code, 'action' => 'module_update_code']
    ) . 
    '</p></div>' .
    '</form>' . 
    '<p>&nbsp;</p>' . 
    '</div>';

    dcPage::helpBlock('translater.lang');

} else {
    $line = '<li><a href="%s"%s>%s</a></li>';
    echo '<h4><i>' . __('Translate your Dotclear plugins and themes') . '</i></h4>' .
        sprintf(
            '<h3><ul class="nice">%s</ul></h3>',
            sprintf(
                $line, 
                $core->adminurl->get('translater', ['type' => 'plugin']), 
                $type == 'plugin' ? ' class="active"' : '',
                __('Translate plugins')) .
            sprintf(
                $line, 
                $core->adminurl->get('translater', ['type' => 'theme']), 
                $type == 'theme' ? ' class="active"' : '',
                __('Translate themes'))
        );

    dcPage::helpBlock('translater.index');
}

echo '</body></html>';