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
declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use dcCore;
use dcNsProcess;
use dcPage;

use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Number,
    Para,
    Select
};

class Config extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            if (version_compare(phpversion(), My::PHP_MIN, '>=')) {
                self::$init = true;
            } else {
                dcCore::app()->error->add(sprintf(__('%s required php >= %s'), My::id(), My::PHP_MIN));
            }
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        // nothing to process
        if (empty($_POST['save'])) {
            return true;
        }

        $s = new Settings();

        try {
            foreach ($s->listSettings() as $key) {
                $s->writeSetting($key, $_POST[$key] ?? '');
            }

            dcPage::addSuccessNotice(
                __('Configuration successfully updated.')
            );
            dcCore::app()->adminurl->redirect(
                'admin.plugins',
                ['module' => My::id(), 'conf' => 1, 'redir' => dcCore::app()->admin->__get('list')->getRedir()]
            );
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::$init) {
            return;
        }

        $s = new Settings();

        echo (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Translation'))))->fields([
                // write_langphp
                (new Para())->items([
                    (new Checkbox('write_langphp', $s->write_langphp))->value(1),
                    (new Label(__('Write .lang.php files'), Label::OUTSIDE_LABEL_AFTER))->for('write_langphp')->class('classic'),
                ]),
                // scan_tpl
                (new Para())->items([
                    (new Checkbox('scan_tpl', $s->scan_tpl))->value(1),
                    (new Label(__('Translate also strings of template files'), Label::OUTSIDE_LABEL_AFTER))->for('scan_tpl')->class('classic'),
                ]),
                // parse_nodc
                (new Para())->items([
                    (new Checkbox('parse_nodc', $s->parse_nodc))->value(1),
                    (new Label(__('Translate only unknow strings'), Label::OUTSIDE_LABEL_AFTER))->for('parse_nodc')->class('classic'),
                ]),
                // hide_default
                (new Para())->items([
                    (new Checkbox('hide_default', $s->hide_default))->value(1),
                    (new Label(__('Hide default modules of Dotclear'), Label::OUTSIDE_LABEL_AFTER))->for('hide_default')->class('classic'),
                ]),
                // parse_comment
                (new Para())->items([
                    (new Checkbox('parse_comment', $s->parse_comment))->value(1),
                    (new Label(__('Write comments in files'), Label::OUTSIDE_LABEL_AFTER))->for('parse_comment')->class('classic'),
                ]),
                // parse_user
                (new Para())->items([
                    (new Checkbox('parse_user', $s->parse_user))->value(1),
                    (new Label(__('Write informations about author in files'), Label::OUTSIDE_LABEL_AFTER))->for('parse_user')->class('classic'),
                ]),
                // parse_userinfo
                (new Para())->items([
                    (new Label(__('User info:')))->for('parse_userinfo'),
                    (new Input('parse_userinfo'))->size(65)->maxlenght(255)->value($s->parse_userinfo),
                ]),
                (new Note())->text(sprintf(
                    __('Following informations can be used: %s'),
                    implode(', ', My::defaultUserInformations())
                ))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Import/Export'))))->fields([
                // import_overwrite
                (new Para())->items([
                    (new Checkbox('import_overwrite', $s->import_overwrite))->value(1),
                    (new Label(__('Overwrite existing languages'), Label::OUTSIDE_LABEL_AFTER))->for('import_overwrite')->class('classic'),
                ]),
                // export_filename
                (new Para())->items([
                    (new Label(__('Name of exported package:')))->for('export_filename'),
                    (new Input('export_filename'))->size(65)->maxlenght(255)->value($s->export_filename),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Backups'))))->fields([
                // backup_auto
                (new Para())->items([
                    (new Checkbox('backup_auto', $s->backup_auto))->value(1),
                    (new Label(__('Make backups when changes are made'), Label::OUTSIDE_LABEL_AFTER))->for('backup_auto')->class('classic'),
                ]),
                // backup_limit
                (new Para())->items([
                    (new Label(__('Limit backups per module to:')))->for('backup_limit')->class('classic'),
                    (new Number('backup_limit'))->min(0)->max(50)->value($s->backup_limit),
                ]),
                (new Note())->text(__('Set to 0 for no limit.'))->class('form-note'),
                // backup_folder
                (new Para())->items([
                    (new Label(__('Store backups in:')))->for('backup_folder'),
                    (new Select('backup_folder'))->default($s->backup_folder)->items(My::backupFoldersCombo()),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Behaviors'))))->fields([
                // start_page
                (new Para())->items([
                    (new Label(__('Default start menu:')))->for('start_page'),
                    (new Select('start_page'))->default($s->start_page)->items(My::startPageCombo()),
                ]),
                // plugin_menu
                (new Para())->items([
                    (new Checkbox('plugin_menu', $s->plugin_menu))->value(1),
                    (new Label(__('Enable menu on plugins page'), Label::OUTSIDE_LABEL_AFTER))->for('plugin_menu')->class('classic'),
                ]),
                // theme_menu
                (new Para())->items([
                    (new Checkbox('theme_menu', $s->theme_menu))->value(1),
                    (new Label(__('Enable menu on themes page'), Label::OUTSIDE_LABEL_AFTER))->for('theme_menu')->class('classic'),
                ]),

            ]),
        ])->render();

        dcPage::helpBlock('translater.config');
    }
}
