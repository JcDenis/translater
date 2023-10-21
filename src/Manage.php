<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    File,
    Form,
    Hidden,
    Input,
    Label,
    Note,
    Para,
    Select,
    Submit,
    Text
};
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       translater manage class.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $current = ManageVars::init();

        // execute action
        try {
            if ($current->action == 'module_create_backups') {
                if (empty($current->module) || empty($_POST['codes'])) {
                    throw new Exception(__('Nothing to backup'));
                }
                foreach ($current->module->getUsedlangs() as $code_id) {
                    if (in_array($code_id, $_POST['codes'])) {
                        $current->module->createBackup($code_id);
                    }
                }

                self::redirect(__('Backup successfully created'));
            }

            if ($current->action == 'module_restore_backup') {
                if (empty($current->module) || empty($_POST['files'])) {
                    throw new Exception(__('Nothing to restore'));
                }
                foreach ($current->module->getBackupsFilename() as $backup_file) {
                    if (in_array($backup_file, $_POST['files'])) {
                        $current->module->restoreBackup($backup_file);
                    }
                }

                self::redirect(__('Backup successfully restored'));
            }

            if ($current->action == 'module_delete_backup') {
                if (empty($current->module) || empty($_POST['files'])) {
                    throw new Exception(__('Nothing to delete'));
                }
                foreach ($current->module->getBackupsFilename() as $backup_file) {
                    if (in_array($backup_file, $_POST['files'])) {
                        $current->module->deleteBackup($backup_file);
                    }
                }

                self::redirect(__('Backup successfully deleted'));
            }

            if ($current->action == 'module_export_pack') {
                if (empty($current->module) || empty($_POST['codes'])) {
                    throw new Exception(__('Nothing to export'));
                }
                $current->module->exportPack($_POST['codes']);

                self::redirect(__('Language successfully exported'));
            }

            if ($current->action == 'module_import_pack') {
                if (empty($current->module) || empty($_FILES['packfile']['name'])) {
                    throw new Exception(__('Nothing to import'));
                }
                $current->module->importPack($_FILES['packfile']);

                self::redirect(__('Language successfully imported'));
            }

            if ($current->action == 'module_add_code') {
                if (empty($current->module) || empty($_POST['code'])) {
                    throw new Exception(__('Nothing to create'));
                }
                $current->module->addLang($_POST['code'], $_POST['from'] ?? '');

                self::redirect(__('Language successfully added'), $_POST['code']);
            }

            if ($current->action == 'module_delete_codes') {
                if (empty($current->module) || empty($_POST['codes'])) {
                    throw new Exception(__('Nothing to delete'));
                }
                foreach ($current->module->getUsedlangs() as $code_id) {
                    if (in_array($code_id, $_POST['codes'])) {
                        $current->module->delLang($code_id);
                    }
                }

                self::redirect(__('Language successfully deleted'));
            }

            if ($current->action == 'module_update_code') {
                if (empty($current->module) || empty($_POST['code']) || empty($_POST['entries'])) {
                    throw new Exception(__('Nothing to update'));
                }
                if (!empty($_POST['update_group'])) {
                    foreach ($_POST['entries'] as $i => $entry) {
                        if (isset($entry['check']) && isset($_POST['multigroup'])) {
                            $_POST['entries'][$i]['group'] = $_POST['multigroup'];
                        }
                    }
                }
                $current->module->updLang($_POST['code'], $_POST['entries']);

                self::redirect(__('Language successfully updated'), $_POST['code']);
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $current = ManageVars::init();

        $breadcrumb = [My::name() => My::manageUrl(['type' => '-'])];
        if (empty($current->type)) {
            $breadcrumb = [My::name() => ''];
        } elseif (empty($current->module)) {
            $breadcrumb[$current->type == 'plugin' ? __('Plugins') : __('Themes')] = '';
        } elseif (empty($current->lang)) {
            $breadcrumb[$current->type == 'plugin' ? __('Plugins') : __('Themes')] = My::manageUrl(['type' => $current->type]);
            $breadcrumb[Html::escapeHTML($current->module->name)]                  = '';
        } elseif (!empty($current->lang)) {
            $breadcrumb[$current->type == 'plugin' ? __('Plugins') : __('Themes')]                  = My::manageUrl(['type' => $current->type]);
            $breadcrumb[Html::escapeHTML($current->module->name)]                                   = My::manageUrl(['type' => $current->type, 'module' => $current->module->id]);
            $breadcrumb[Html::escapeHTML(sprintf(__('%s language edition'), $current->lang->name))] = '';
        }

        Page::openModule(
            My::name(),
            Page::jsPageTabs() .
            My::cssLoad('backend') .
            Page::jsJson('translater', [
                'title_add_detail' => __('Use this text'),
                'image_field'      => My::fileURL('/img/field.png'),
                'image_toggle'     => My::fileURL('/img/toggle.png'),
            ]) .
            My::jsLoad('backend') .

            # --BEHAVIOR-- translaterAdminHeaders
            App::behavior()->callBehavior('translaterAdminHeaders')
        );

        echo
        Page::breadcrumb($breadcrumb) .
        Notices::getNotices();

        if (empty($current->module) && $current->type != '') {
            // modules list
            echo '<form id="theme-form" method="post" action="' . My::manageUrl(['type' => 'plugin']) . '">';

            $res     = '';
            $modules = $current->translater->getModules($current->type);
            uasort($modules, fn ($a, $b) => strtolower($a->name) <=> strtolower($b->name));
            foreach ($modules as $module) {
                if ($current->translater->hide_default && in_array($module->id, My::defaultDistribModules($current->type))) {
                    continue;
                }
                if ($module->root_writable) {
                    $res .= sprintf(
                        '<tr class="line"><td class="nowrap minimal"><a href="%s" title="%s">%s</a></td>',
                        My::manageUrl(['type' => $module->type, 'module' => $module->id]),
                        Html::escapeHTML(sprintf(__('Translate module %s'), __($module->name))),
                        Html::escapeHTML($module->id)
                    );
                } else {
                    $res .= sprintf(
                        '<tr class="line offline"><td class="nowrap">%s</td>',
                        Html::escapeHTML($module->id)
                    );
                }
                $codes = $module->getLangs();
                foreach ($codes as $code_id => $code_name) {
                    if (!is_string($code_name)) {
                        continue;
                    }
                    if ($module->root_writable) {
                        $codes[$code_id] = sprintf(
                            '<a class="wait maximal nowrap" title="%s" href="%s">%s (%s)</a>',
                            Html::escapeHTML(sprintf(__('Edit language %s of module %s'), Html::escapeHTML($code_name), __($module->name))),
                            My::manageUrl(['type' => $module->type, 'module' => $module->id, 'lang' => $code_id]),
                            Html::escapeHTML($code_name),
                            $code_id
                        );
                    } else {
                        $codes[$code_id] = Html::escapeHTML($code_name) . '(' . $code_id . ')';
                    }
                }
                $res .= sprintf(
                    '<td class="nowrap maximal">%s</td><td class="nowrap minimal">%s</td><td class="nowrap minimal count">%s</td></tr>',
                    implode(', ', $codes),
                    Html::escapeHTML(__($module->name)),
                    $module->version
                );
            }
            if ($res) {
                echo '
                <div class="table-outer">
                <table class="clear">
                <caption>' . sprintf(__('Modules list of type "%s"'), $current->type) . '</caption>
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

            Page::helpBlock('translater.type');
        } elseif (!empty($current->module) && empty($current->lang)) {
            $codes        = $current->module->getUsedLangs();
            $backups      = $current->module->getBackups();
            $unused_codes = $current->module->getUnusedLangs();

            // module summary
            echo '<h3>' . sprintf(__('Module %s %s by %s'), $current->module->name, $current->module->version, $current->module->author) . '</h3>
            <ul class="nice col">
            <li><strong>' . __('Root') . '</strong> ' . $current->module->root . '</li>
            <li><strong>' . __('Locales') . '</strong> ' . $current->module->locales . '</li>
            <li><strong>' . __('Backups') . '</strong> ' . $current->module->getBackupRoot() . '</li>
            </ul>
            <p>&nbsp;</p>';

            // existing languages
            if (count($codes)) {
                echo
                '<div class="clear fieldset"><h3>' . __('Translations') . '</h3>' .
                '<form id="module-translations-form" method="post" action="' . My::manageUrl() . '">' .
                '<table class="clear maximal">' .
                '<caption>' . __('Existing languages translations') . '</caption>' .
                '<tr>' .
                '<th class="nowrap" colspan="2">' . __('Language') . '</th>' .
                '<th class="nowrap">' . __('Code') . '</th>' .
                '<th class="nowrap">' . __('Backups') . '</th>' .
                '<th class="nowrap">' . __('Last backup') . '</th>' .
                '</tr>';

                foreach ($codes as $code_name => $code_id) {
                    echo
                    '<tr class="line">' .
                    '<td class="minimal">' . (new Checkbox(['codes[]', 'existing_code_' . $code_id]))->value($code_id)->render() . '</td>' .
                    '<td class="nowrap">' .
                    '<a href="' .
                        My::manageUrl(['type' => $current->module->type, 'module' => $current->module->id, 'lang' => $code_id])
                         . '" title="' . sprintf(__('Edit %s language'), Html::escapeHTML($code_name)) . '">' . $code_name . '</a>' .
                    '</td>' .
                    '<td class="nowrap maximal"> ' . $code_id . '</td>';

                    if (isset($backups[$code_id])) {
                        $time[$code_id] = 0;
                        foreach ($backups[$code_id] as $file => $info) {
                            $time[$code_id] = isset($time[$code_id]) && $time[$code_id] > $info['time'] ?
                                $time[$code_id] : $info['time'];
                        }
                        echo
                        '<td class="nowrap">' . count($backups[$code_id]) . '</td>' .
                        '<td class="nowrap"> ' .
                        Date::str('%Y-%m-%d %H:%M', (int) $time[$code_id], (string) App::blog()->settings()->get('system')->get('blog_timezone')) .
                        '</td>';
                    } else {
                        echo '<td class="nowrap">' . __('no backups') . '</td><td class="maximal nowrap">-</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>
                <div class="two-cols">
                <p class="col checkboxes-helpers"></p>' .

                (new Para())->class('col right')->items([
                    (new Text('', __('Selected languages action:'))),
                    (new Select('action'))->items([
                        __('Backup languages') => 'module_create_backups',
                        __('Delete languages') => 'module_delete_codes',
                        __('Export languages') => 'module_export_pack',
                    ]),
                    (new Submit('do-action'))->value(__('ok')),
                    ... My::hiddenFields([
                        'type'   => $current->module->type,
                        'module' => $current->module->id,
                    ]),
                ])->render() .
                '</div></form><p>&nbsp;</p></div>';
            }

            // backups
            if (!empty($codes) || !empty($backups)) {
                // delete / retore backups
                if (!empty($backups)) {
                    echo '<div class="fieldset"><h3>' . __('Backups') . '</h3>' .
                    '<form id="module-backups-form" method="post" action="' . My::manageUrl() . '">' .
                    '<table class="clear">' .
                    '<caption>' . __('Existing languages backups') . '</caption>' .
                    '<tr>' .
                    '<th class="nowrap" colspan="2">' . __('Language') . '</th>' .
                    '<th class="nowrap">' . __('Code') . '</th>' .
                    '<th class="nowrap">' . __('Date') . '</th>' .
                    '<th class="nowrap">' . __('File') . '</th>' .
                    '<th class="nowrap">' . __('Size') . '</th>' .
                    '</tr>';

                    $table_line = '<tr class="line">' .
                        '<td class="minimal">%s</td>' .
                        '<td class="nowrap"><label for="%s">%s</label></td>' .
                        '<td class="nowrap maximal">%s</td>' .
                        '<td class="nowrap count">%s</td>' .
                        '<td class="nowrap">%s</td>' .
                        '<td class="nowrap count">%s</td>' .
                        '</tr>';

                    $i = 0;
                    foreach ($backups as $backup_codes) {
                        foreach ($backup_codes as $backup_file => $backup_code) {
                            $i++;
                            $form_id = 'form_file_' . (is_string($backup_code['code']) ? $backup_code['code'] : '') . (is_string($backup_code['time']) ? $backup_code['time'] : '');
                            echo sprintf(
                                $table_line,
                                (new Checkbox(['files[]', $form_id]))->value($backup_file)->render(),
                                $form_id,
                                is_string($backup_code['name']) ? $backup_code['name'] : 'unknown',
                                is_string($backup_code['code']) ? $backup_code['code'] : 'unknown',
                                Date::str(
                                    App::blog()->settings()->get('system')->get('date_format') . ' ' . App::blog()->settings()->get('system')->get('time_format'),
                                    (int) $backup_code['time'],
                                    App::blog()->settings()->get('system')->get('blog_timezone')
                                ),
                                is_array($backup_code['path']) ? $backup_code['path']['basename'] : '',
                                Files::size((int) $backup_code['size'])
                            );
                        }
                    }
                    echo '
                    </table>
                    <div class="two-cols">
                    <p class="col checkboxes-helpers"></p>' .

                    (new Para())->class('col right')->items([
                        (new Text('', __('Selected backups action:'))),
                        (new Select('action'))->items([
                            __('Restore backups') => 'module_restore_backup',
                            __('Delete backups')  => 'module_delete_backup',
                        ]),
                        (new Submit('do-action'))->value(__('ok')),
                        ... My::hiddenFields([
                            'type'   => $current->module->type,
                            'module' => $current->module->id,
                        ]),
                    ])->render() .
                    '</div></form><p>&nbsp;</p></div>';
                }
            }

            echo '<div class="two-cols">';

            // add language
            if (!empty($unused_codes)) {
                echo '<div class="col fieldset"><h3>' . __('Add language') . '</h3>
                <form id="module-code-create-form" method="post" action="' . My::manageUrl() . '">' .
                (new Para())->class('field')->items([
                    (new Label(__('Select language:')))->for('code'),
                    (new Select(['code']))->default((string) App::auth()->getInfo('user_lang'))->items(array_merge(['-' => '-'], $unused_codes)),
                ])->render();

                if (empty($codes)) {
                    echo (new Para())->items([(new Hidden(['from'], ''))])->render();
                } else {
                    echo
                    (new Para())->class('field')->items([
                        (new Label(__('Copy from language:')))->for('from'),
                        (new Select(['from']))->items(array_merge(['-' => ''], $codes)),
                        (new Note())->class('form-note')->text(__('optionnal')),
                    ])->render();
                }
                echo
                (new Para())->items([
                    (new Submit(['save']))->value(__('Create')),
                    ... My::hiddenFields([
                        'type'   => $current->module->type,
                        'module' => $current->module->id,
                        'action' => 'module_add_code',
                    ]),
                ])->render() .
                '</form><p>&nbsp;</p></div>';
            }

            // Import
            echo '<div class="col fieldset"><h3>' . __('Import') . '</h3>' .
            (new Form('module-pack-import-form'))->method('post')->action(My::manageUrl())->extra('enctype="multipart/form-data"')->fields([
                (new Para())->items([
                    (new Label(__('Select languages package to import:')))->for('packfile'),
                    (new File('packfile')),

                ]),
                (new Para())->items([
                    (new Submit(['save']))->value(__('Import')),
                    ... My::hiddenFields([
                        'type'   => $current->module->type,
                        'module' => $current->module->id,
                        'action' => 'module_import_pack',
                    ]),
                ]),
            ])->render() .
            '<p>&nbsp;</p></div>';

            echo '</div>';

            Page::helpBlock('translater.module');
        } elseif (!empty($current->lang)) {
            $lines               = $current->lang->getMessages();
            $allowed_l10n_groups = [];

            echo
            '<div id="lang-form">' .
            '<form id="lang-edit-form" method="post" action="' . My::manageUrl() . '">' .
            '<table class="table-outer">' .
            '<caption>' . sprintf(__('List of %s localized strings'), count($lines)) . '</caption>' .
            '<tr>' .
            '<th colspan="2">' . __('Group') . '</th>' .
            '<th>' . __('String') . '</th>' .
            '<th>' . __('Translation') . '</th>' .
            '<th>' . __('Existing') . '</th>' .
            '<th>' . __('File') . '</th>' .
            '</tr>';

            $table_line = '<tr class="line%s">' .
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
            foreach ($lines as $msgid => $rs) {
                $in_dc    = $rs['in_dc'] && $current->translater->parse_nodc ? true : false;
                $t_msgstr = $t_files = $strin = [];

                foreach ($rs['o_msgstrs'] as $o_msgstr) {
                    if (!isset($strin[$o_msgstr['msgstr'][0]])) {
                        $strin[$o_msgstr['msgstr'][0]] = [];
                    }
                    $strin[$o_msgstr['msgstr'][0]][] = ['module' => $o_msgstr['module'], 'file' => $o_msgstr['file']];
                }
                foreach ($strin as $k => $v) {
                    $res = [];
                    foreach ($v as $str) {
                        $res[] = sprintf($table_li, Html::escapeHTML($str['module'] . ':' . $str['file']));
                    }
                    $t_msgstr[] = sprintf($table_ul, Html::escapeHTML((string) $k), implode('', $res));
                }

                if (!empty($rs['files'][0])) {
                    if (count($rs['files']) == 1) {
                        $t_files[] = $rs['files'][0][0] . ':' . $rs['files'][0][1];
                    } else {
                        $res = [];
                        foreach ($rs['files'] as $location) {
                            $res[] = sprintf($table_li, implode(' : ', $location));
                        }
                        $t_files[] = sprintf($table_ul, sprintf(__('%s occurrences'), count($rs['files'])), implode('', $res));
                        ;
                    }
                }

                echo sprintf(
                    $table_line,
                    $in_dc ? ' offline' : ' translaterline',
                    (new Checkbox(['entries[' . $i . '][check]']))->value(1)->render(),
                    (new Select(['entries[' . $i . '][group]']))->default($rs['group'])->items(My::l10nGroupsCombo())->disabled($in_dc)->render(),
                    Html::escapeHTML($msgid),
                    (new Hidden(['entries[' . $i . '][msgid]'], Html::escapeHTML($msgid)))->render() .
                    (new Input(['entries[' . $i . '][msgstr][0]']))->size(48)->maxlenght(255)->value(Html::escapeHTML($rs['msgstr'][0]))->disabled($in_dc)->render(),
                    implode('', $t_msgstr),
                    implode('', $t_files)
                );

                if (!empty($rs['plural'])) {
                    $t_msgstr = $strin = [];
                    foreach ($current->lang->plural as $j => $plural) {
                        foreach ($rs['o_msgstrs'] as $o_msgstr) {
                            if (isset($o_msgstr['msgstr'][$j + 1])) {
                                if (!isset($strin[$o_msgstr['msgstr'][$j + 1]])) {
                                    $strin[$o_msgstr['msgstr'][$j + 1]] = [];
                                }
                                $strin[$o_msgstr['msgstr'][$j + 1]][] = ['module' => $o_msgstr['module'], 'file' => $o_msgstr['file']];
                            }
                        }
                        foreach ($strin as $k => $v) {
                            $res = [];
                            foreach ($v as $str) {
                                $res[] = sprintf($table_li, Html::escapeHTML($str['module'] . ':' . $str['file']));
                            }
                            $t_msgstr[] = sprintf($table_ul, Html::escapeHTML((string) $k), implode('', $res));
                        }

                        echo sprintf(
                            $table_line,
                            $in_dc ? ' offline' : ' translaterline',
                            '+',
                            sprintf(__('Plural "%s"'), $plural),
                            sprintf(__('Plural form of "%s"'), $rs['plural']),
                            (new Hidden(['entries[' . $i . '][msgid_plural]'], Html::escapeHTML($rs['plural'])))->render() .
                            (new Input(['entries[' . $i . '][msgstr][' . ($j + 1) . ']']))->size(48)->maxlenght(255)->value(Html::escapeHTML($rs['msgstr'][$j + 1] ?? ''))->disbaled($in_dc)->render(),
                            implode('', $t_msgstr),
                            ''
                        );
                    }
                }
                $i++;
            }
            echo sprintf(
                $table_line,
                ' offline',
                (new Checkbox(['entries[' . $i . '][check]']))->value(1)->render(),
                (new Select(['entries[' . $i . '][group]']))->items(My::l10nGroupsCombo())->default('main')->render(),
                (new Input(['entries[' . $i . '][msgid]']))->size(48)->maxlenght(255)->render(),
                (new Input(['entries[' . $i . '][msgstr][0]']))->size(48)->maxlenght(255)->render(),
                '',
                ''
            );
            echo
            '</table>' .

            '<div class="two-cols">' .
            '<div class="col left">' .
            '<p class="checkboxes-helpers"></p>' .

            (new Para())->items([
                (new Checkbox('update_group'))->value(1),
                (new Label(__('Change the group of the selected translations to:'), Label::OUTSIDE_LABEL_AFTER))->for('update_group')->class('classic'),
                (new Select('multigroup'))->items(My::l10nGroupsCombo()),
            ])->render() .
            '</div>' .
            (new Para())->class('col right')->items([
                (new Submit('do-action'))->value(__('Save') . ' (s)')->accesskey('s'),
                ... My::hiddenFields([
                    'type'   => (string) $current->module?->type,
                    'module' => (string) $current->module?->id,
                    'action' => 'module_update_code',
                    'lang'   => $current->lang->code,
                    'code'   => $current->lang->code, //?
                ]),
            ])->render() .
            '</div>' .
            '</form>' .
            '<p>&nbsp;</p>' .
            '</div>';

            Page::helpBlock('translater.lang');
        } else {
            $line = '<li><a href="%s"%s>%s</a></li>';
            echo '<h4><i>' . __('Translate your Dotclear plugins and themes') . '</i></h4>' .
                sprintf(
                    '<h3><ul class="nice">%s</ul></h3>',
                    sprintf(
                        $line,
                        My::manageUrl(['type' => 'plugin']),
                        $current->type == 'plugin' ? ' class="active"' : '',
                        __('Translate plugins')
                    ) .
                    sprintf(
                        $line,
                        My::manageUrl(['type' => 'theme']),
                        $current->type == 'theme' ? ' class="active"' : '',
                        __('Translate themes')
                    )
                );

            Page::helpBlock('translater.index');
        }

        Page::closeModule();
    }

    private static function redirect(string $msg, ?string $lang = null): void
    {
        $current = ManageVars::init();

        $redir = [
            'type'   => $current->type,
            'module' => (string) $current->module?->id,
        ];
        if ($lang) {
            $redir['lang'] = $lang;
        }

        Notices::addSuccessNotice($msg);
        My::manageUrl($redir);
    }
}
