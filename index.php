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

#
# Init some variables
#

$translater = new dcTranslater($core);

$succes = [
    'save_setting' => __('Configuration successfully updated'),
    'update_lang' => __('Translation successfully updated'),
    'add_lang' => __('Translation successfully created'),
    'delete_lang' => __('Translation successfully deleted'),
    'create_backup' => __('Backups successfully create'),
    'restore_backup' => __('Backups successfully restored'),
    'delete_backup' => __('Backups successfully deleted'),
    'import_pack' => __('Package successfully imported'),
    'export_pack' => __('Package successfully exported')
];

$errors = [
    'save_setting' => __('Failed to update settings: %s'),
    'update_lang' => __('Failed to update translation: %s'),
    'add_lang' => __('Failed to create translation: %s'),
    'delete_lang' => __('Failed to delete translation: %s'),
    'create_backup' => __('Failed to create backups: %s'),
    'restore_backup' => __('Failed to restore backups: %s'),
    'delete_backup' => __('Failed to delete backups: %s'),
    'import_pack' => __('Failed to import package: %s'),
    'export_pack' => __('Failed to export package: %s')
];

$p_url  = 'plugin.php?p=translater';
$start_page = @explode('_', $translater->start_page);
if (count($start_page) < 2) {
    $start_page[1] = '';
}

#
# Parse request
#

$msg = isset($_REQUEST['msg']) ? $_REQUEST['msg'] : '';
$part = isset($_REQUEST['part']) ? $_REQUEST['part'] : $start_page[0];
$tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : ''; 
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : $start_page[1];
$module = isset($_REQUEST['module']) ? $_REQUEST['module'] : '';
$from = isset($_POST['from']) && $_POST['from'] != '-' ? $_POST['from'] : '';
$lang = isset($_REQUEST['lang']) && $_REQUEST['lang'] != '-' ? $_REQUEST['lang'] : '';

if ($type == '-' || $module == '-') {
    $type = $module = '';
}

#
# Manage action
#

switch ($action) {
    case '':
    break;

    /**
     * Create lang for a module
     */
    case 'module_add_lang':
        try {
            if (empty($lang)) {
                throw new Exception(__('No lang to create'));
            }
            $translater->addLang($module, $lang, $from);

            http::redirect($p_url . '&part=lang&module=' . $module . '&type=' . $type . '&lang=' . $lang . '&msg=add_lang');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Delete lang for a module
     */
    case 'module_delete_lang':
        try {
            if (empty($lang)) {
                throw new Exception(__('No lang to delete'));
            }
            $translater->delLang($module, $lang);

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-lang&msg=delete_lang');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
    }
    break;

    /**
     * Create backup for a module
     */
    case 'module_create_backup':
        try {
            if (empty($_POST['modules']) || empty($_POST['langs'])) {
                throw new Exception(__('No lang to backup'));
            }

            foreach($_POST['modules'] as $b_module) {
                $b_list = $translater->listLangs($b_module);

                foreach($_POST['langs'] as $b_lang) {
                    if (isset($b_list[$b_lang])) {
                        $translater->createBackup($b_module, $b_lang);
                    }
                }
            }

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-backup&msg=creat_backup');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Restore backup for a module
     */
    case 'module_restore_backup':
        try {
            if (empty($_POST['modules']) || empty($_POST['files'])) {
                throw New Exception(__('No blackup to restore'));
            }

            sort($_POST['files']);
            $done = false;
            foreach($_POST['modules'] as $b_module) {
                $b_list = $translater->listBackups($b_module, true);

                foreach($_POST['files'] as $b_file) {
                    if (in_array($b_file, $b_list)) {
                        $translater->restoreBackup($b_module, $b_file);
                        $done = true;
                    }
                }
            }
            if (!$done) {
                throw new Exception(__('No bakcup to to restore'));
            }

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-backup&msg=restore_backup');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Delete backup for a module
     */
    case 'module_delete_backup':
        try {
            if (empty($_POST['modules']) || empty($_POST['files'])) {
                throw New Exception(__('No backup to delete'));
            }

            $done = false;
            foreach($_POST['modules'] as $b_module) {
                $b_list = $translater->listBackups($b_module, true);

                foreach($_POST['files'] as $b_file) {
                    if (in_array($b_file, $b_list)) {
                        $translater->deleteBackup($b_module, $b_file);
                        $done = true;
                    }
                }
            }
            if (!$done) {
                throw new Exception(__('No backup to delete'));
            }

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-backup&msg=delete_backup');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action],$e->getMessage()));
        }
    break;

    /**
     * Import language package for a module
     */
    case 'module_import_pack':
        try {
            if (empty($_FILES['packfile']['name'])) {
                throw new Exception(__('Nothing to import'));
            }
            $translater->importPack($_POST['modules'], $_FILES['packfile']);

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-pack&msg=import_pack');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Export language package for a module
     */
    case 'module_export_pack':
        try {
            if (empty($_POST['modules']) || empty($_POST['entries'])) {
                throw new Exception(__('Nothing to export'));
            }
            $translater->exportPack($_POST['modules'], $_POST['entries']);

            http::redirect($p_url . '&part=module&module=' . $module . '&type=' . $type . '&tab=module-pack&msg=export_pack');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Update language
     */
    case 'update_lang':
        try {
            if (empty($_POST['entries']) || empty($lang) || empty($module)) {
                throw new Exception(__('No language to update'));
            }
            foreach($_POST['entries'] as $i => $entry) {
                if (isset($entry['check']) && isset($_POST['multigroup'])) {
                    $_POST['entries'][$i]['group'] = $_POST['multigroup'];
                    unset($_POST['entries'][$i]['check']);
                }
            }
            $translater->updLang($module, $lang, $_POST['entries']);

            http::redirect($p_url . '&part=lang&module=' . $module . '&type=' . $type . '&lang=' . $lang . '&msg=' . $action);
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Import language packages
     */
    case 'import_pack':
        try {
            if (empty($_FILES['packfile']['name'])) {
                throw new Exception(__('Nothing to import'));
            }
            $translater->importPack($_POST['modules'], $_FILES['packfile']);

            http::redirect($p_url . '&part=pack&msg=' . $action . '&tab=pack-import');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Export language packages
     */
    case 'export_pack':
        try {
            if (empty($_POST['modules']) || empty($_POST['entries'])) {
                throw new Exception(__('Nothing to export'));
            }
            $translater->exportPack($_POST['modules'], $_POST['entries']);

            http::redirect($p_url . '&part=pack&msg=' . $action . '&tab=pack-export');
        } catch (Exception $e) {
            $core->error->add(sprintf($errors[$action], $e->getMessage()));
        }
    break;

    /**
     * Modules / Unknow / None
     */
    default:
    break;
}

#
# Fill in title and prepare display
#

switch ($part) {
    /**
     * Modules
     */
    case 'modules':
        $title = '<span class="page-title">' . ($type == 'theme' ? __('Themes') : __('Plugins')) . '</span>';
    break;

    /**
     * Module
     */
    case 'module':
        # Get info about requested module
        try {
            $M = $translater->getModule($module, $type);
        } catch(Exception $e) {
            $core->error->add(sprintf(__('Failed to launch translater: %s'), $e->getMessage()));
            $action = $module = $type = '';
            $M = false;
        }
        if (!empty($module) && !empty($type) && !$M) {
            $action = $module = $type = '';
            $M = false;
        }

        $M->langs = $translater->listLangs($module);
        $M->backups = $translater->listBackups($module);
        $M->unused_langs = array_flip(array_diff($translater->getIsoCodes(), $M->langs));
        $M->used_langs = array_flip(array_diff($M->langs, array_flip($translater->getIsoCodes())));
        $allowed_groups = array_combine(
            dcTranslater::$allowed_l10n_groups,
            dcTranslater::$allowed_l10n_groups
        );

        $title = 
        '<a href="' . $p_url . '&amp;part=modules&type=' . $type . '">' . ($type == 'theme' ? __('Themes') : __('Plugins')) . '</a>' .
        ' &rsaquo; <span class="page-title">' . $module . '</span>';
    break;

    /**
     * Lang
     */
    case 'lang':

        # Get infos on module wanted
        try {
            $M = $translater->getModule($module, $type);

            # Retrieve some infos
            $M->langs = $translater->listLangs($module);
            $M->backups = $translater->listBackups($module);
            $M->unused_langs = array_flip(array_diff($translater->getIsoCodes(), $M->langs));
            $M->used_langs = array_flip(array_diff($M->langs, array_flip($translater->getIsoCodes())));
            $allowed_groups = array_combine(
                dcTranslater::$allowed_l10n_groups,
                dcTranslater::$allowed_l10n_groups
            );
        } catch(Exception $e) {
            $core->error->add(sprintf(__('Failed to launch translater: %s'), $e->getMessage()));
            $action = $module = $type = '';
            $M = false;
        }
        if (!empty($module) && !empty($type) && !$M) {
            $action = $module = $type = '';
            $M = false;
        }

        $title =
        '<a href="' . $p_url . '&amp;part=modules&type=' . $type . '">' . ($type == 'theme' ? __('Themes') : __('Plugins')) . '</a>' .
        ' &rsaquo; ' .
        '<a href="' . $p_url . '&amp;part=module&type=' . $type . '&module=' . $module . '&amp;tab=module-lang">' . $module . '</a>';
        if (!empty($M->langs) && isset($M->langs[$lang])) {
            $title .= ' &rsaquo; <span class="page-title">' . $M->langs[$lang] . '</span>';
        }
    break;

    /**
     * Import/Export (pack)
     */
    case 'pack':
        $title = '<span class="page-title">' . __('Import/Export') . '</span>';
    break;
}

#
# Display page
#

echo '
<html>
<head><title>' . __('Translater') . '</title>' .
dcPage::jsPageTabs($tab) .
dcPage::jsLoad('js/_posts_list.js') .
dcPage::jsLoad('index.php?pf=translater/js/jquery.translater.js');

if ('' != $translater->proposal_tool) {
    echo  
    '<style type="text/css">' .
    ' .addfield, .togglelist { border: none; }' .
    "</style>\n" .
    "<script type=\"text/javascript\"> \n" .
    "//<![CDATA[\n" .
    " \$(function(){if(!document.getElementById){return;} \n" .
    "  \$.fn.translater.defaults.url = '" . html::escapeJS('services.php') . "'; \n" .
    "  \$.fn.translater.defaults.func = '" . html::escapeJS('getProposal') . "'; \n" .
    "  \$.fn.translater.defaults.from = '" . html::escapeJS($translater->proposal_lang) . "'; \n" .
    "  \$.fn.translater.defaults.to = '" . html::escapeJS($lang) . "'; \n" .
    "  \$.fn.translater.defaults.tool = '" . html::escapeJS($translater->proposal_tool) . "'; \n" .
    "  \$.fn.translater.defaults.title = '" . html::escapeJS(sprintf(__('Use this %s translation:'), $translater->proposal_tool)) . "'; \n" .
    "  \$.fn.translater.defaults.title_go = '" . html::escapeJS(sprintf(__('Translate this text with %s'), $translater->proposal_tool)) . "'; \n" .
    "  \$.fn.translater.defaults.title_add = '" . html::escapeJS(__('Use this text')) . "'; \n" .
    "  \$('.translaterline').translater(); \n" .
    "})\n" .
    "//]]>\n" .
    "</script>\n";
}

# --BEHAVIOR-- translaterAdminHeaders
$core->callBehavior('translaterAdminHeaders');

echo 
'</head>
<body><h2>' . __('Translater') . ' &rsaquo; ' . $title;
if ($part == 'pack' || $type != 'plugin') {
    echo ' / <a href="' . $p_url . '&amp;part=modules&amp;type=plugin">' . __('Plugins') . '</a>';
}
if ($part == 'pack' || $type != 'theme') {
    echo ' / <a href="' . $p_url . '&amp;part=modules&amp;type=theme">' . __('Themes') . '</a>';
}
if ($part != 'pack') {
    echo ' / <a href="' . $p_url . '&amp;part=pack">' . __('Import/Export') . '</a>';
}
echo
'</h2>';

if (isset($succes[$msg])) {
    dcPage::success($succes[$msg]);
}

switch ($part) {
    /**
     * Modules
     */
    case 'modules':
        echo '<form id="theme-form" method="post" action="' . $p_url . '">';

        $res = '';
        foreach ($translater->listModules($type) as $k => $v) {
            if ($translater->hide_default && in_array($k, dcTranslater::$default_dotclear_modules[$type])) {
                continue;
            }

            if ($v['root_writable']) {
                $res .= 
                '<tr class="line">' .
                '<td class="nowrap">' .
                '<a href="' . $p_url . '&amp;part=module&amp;type=' . $type . '&amp;module=' . $k . '" title="' .
                sprintf(
                    ($type == 'theme' ? 
                        html::escapeHTML(__('Translate theme "%s" (by %s)')) : 
                        html::escapeHTML(__('Translate plugin "%s" (by %s)'))
                    ),
                    html::escapeHTML(__($v['name'])), html::escapeHTML($v['author'])
                ) .
                '">' . $k . '</a></td>';
            } else {
                $res .= 
                '<tr class="line offline">' .
                '<td class="nowrap">' . $k . '</td>';
            }
            $res .= 
            '<td class="nowrap">' . $v['version'] . '</td>' .
            '<td class="nowrap">';

            $array_langs = array();
            foreach ($translater->listLangs($k) as $lang_name => $lang_infos) {
                $array_langs[$lang_name] = 
                '<a class="wait maximal nowrap" title="' . __('Edit translation') . '" href="' . $p_url . '&amp;part=lang&amp;type=' . $type . '&amp;module=' . $k . '&amp;lang=' . $lang_name . '">' .
                $lang_name . '</a>';
            }
            $res .=  implode(', ', $array_langs) .
            '</td>' .
            '</tr>';
        }
        if ($res) {
            echo '
            <table class="clear">
            <tr>
            <th>' . __('Id') . '</th>
            <th class="nowrap">' . __('Version') . '</th>
            <th class="nowrap maximal">' . __('Languages') . '</th>
            </tr>' .
            $res .
            '</table>';

        } else {
            echo '<tr><td colspan="6">' . __('There is no editable modules') . '</td></tr>';
        }
        echo '
        <p>&nbsp;</p>
        </form>';
    break;

    /**
     * Module
     */
    case 'module':
        # Summary
        echo '
        <div class="multi-part" id="module-summary" title="' . __('Summary') . '">
        <h3>' . __('Module') . '</h3>
        <table class="clear maximal">
        <tr><th colspan="2">' . __('About') . '</th></tr>
        <tr class="line">
        <td class="nowrap">' . __('Name') . '</td><td class="nowrap"> ' . $M->name . '</td>
        </tr><tr class="line">
        <td class="nowrap">' . __('Version') . '</td><td class="nowrap"> ' . $M->version . '</td>
        </tr><tr class="line">
        <td class="nowrap">' . __('Author') . '</td><td class="nowrap"> ' . $M->author . '</td>
        </tr><tr class="line">
        <td class="nowrap">' . __('Type') . '</td><td class="nowrap"> ' . $M->type . '</td>
        </tr><tr class="line">
        <td class="nowrap">' . __('Root') . '</td><td class="nowrap"> ' . $M->root . '</td>
        </tr><tr class="line">
        <td class="nowrap">' . __('Backups') . '</td><td class="nowrap"> ' . 
            $translater->getBackupFolder($module) . '</td>
        </tr>
        </table>
        <p>&nbsp;</p>';

        if (count($M->langs)) {
            echo 
            '<h3>' . __('l10n') . '</h3>' . 
            '<table class="clear maximal">' . 
            '<tr>' . 
            '<th>' . __('Languages') . '</th>' . 
            '<th>' . __('Code') . '</th>' . 
            '<th>' . __('Backups') . '</th>' . 
            '<th>' . __('Last backup') . '</th>' . 
            '</tr>';

            foreach($M->langs AS $lang => $name) {
                echo 
                '<tr class="line">' . 
                '<td class="nowrap">' . 
                '<a href="' . $p_url . '&amp;part=lang&amp;type=' . $type . '&amp;module=' . $module . '&amp;lang=' . $lang . '">' . $name . '</a>' . 
                '</td>' . 
                '<td class="nowrap"> ' . $lang . '</td>';

                if (isset($M->backups[$lang])) {
                    foreach($M->backups[$lang] AS $file => $info) {
                        $time[$lang] = isset($time[$lang]) && $time[$lang] > $info['time'] ? 
                            $time[$lang] : $info['time'];
                    }
                    echo 
                    '<td class="nowrap">' . count($M->backups[$lang]) . '</td>' . 
                    '<td class="nowrap"> ' . 
                    dt::str('%Y-%m-%d %H:%M', $time[$lang], $core->blog->settings->system->blog_timezone) . 
                    '</td>';
                } else {
                    echo '<td class="nowrap" colspan="4">' . __('no backup') . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        # Add/Remove/Edit lang
        echo '<div class="multi-part" id="module-lang" title="' . __('Translations') . '">';

        # Edit lang
        if (!empty($M->langs)) {
            echo '
            <h3>' . __('Edit language') . '</h3>
            <form id="module-lang-edit-form" method="post" action="' . $p_url . '">
            <p>' . __('Select language:') . ' ' .  
            form::combo(array('lang'), $M->used_langs,$lang) . '</p>
            <p><input type="submit" name="save" value="' . __('Edit translation') . '" />' . 
            $core->formNonce() . 
            form::hidden(array('type'), $type) . 
            form::hidden(array('module'), $module) . 
            form::hidden(array('action'), '') . 
            form::hidden(array('part'), 'lang') . 
            form::hidden(array('p'), 'translater') . '
            </p>
            </form>
            <p>&nbsp;</p>';
        }

        # New lang
        if (!empty($M->unused_langs)) {
            echo '
            <h3>' . __('Add language') . '</h3>
            <form id="muodule-lang-create-form" method="post" action="' . $p_url . '">
            <p class="nowrap">' . __('Select language:') . ' ' .  
            form::combo(array('lang'), array_merge(array('-'=>'-'), $M->unused_langs), $core->auth->getInfo('user_lang')) . '</p>';
            if (!empty($M->used_langs)) {
                echo 
                '<p>' . __('Copy from language:') . ' ' .  
                form::combo(array('from'),array_merge(array('-'=>'-'), $M->used_langs)) . 
                ' (' . __('Optionnal') . ')</p>';
            } else {
                echo '<p>' . form::hidden(array('from'), '') . '</p>';
            }
            echo '
            <p><input type="submit" name="save" value="' . __('Add translation') . '" />' . 
            $core->formNonce() . 
            form::hidden(array('type'), $type) . 
            form::hidden(array('module'), $module) . 
            form::hidden(array('action'), 'module_add_lang') . 
            form::hidden(array('part'), 'module') . 
            form::hidden(array('tab'), 'module-lang') . 
            form::hidden(array('p'), 'translater') . '
            </p>
            </form>
            <p>&nbsp;</p>';
        }

        # Delete lang
        if (!empty($M->used_langs)) {
            echo '
            <h3>' . __('Delete language') . '</h3>
            <form id="module-lang-delete-form" method="post" action="' . $p_url . '">
            <p>' . __('Select language:') . ' ' .  
            form::combo(array('lang'), array_merge(array('-'=>'-'), $M->used_langs)) . '</p>
            <p><input type="submit" name="save" value="' . __('Delete translation') . '" />' . 
            $core->formNonce() . 
            form::hidden(array('type'), $type) . 
            form::hidden(array('module'), $module) . 
            form::hidden(array('action'), 'module_delete_lang') . 
            form::hidden(array('part'), 'module') . 
            form::hidden(array('tab'), 'module-lang') . 
            form::hidden(array('p'), 'translater') . '
            </p>
            </form>
            <p>&nbsp;</p>';
        }
        echo '</div>';

        # Create/delete/restore backups
        if (!empty($M->used_langs) || !empty($M->backups)) {

            echo '<div class="multi-part" id="module-backup" title="' . __('Backups') . '">';

            if (!empty($M->used_langs)) {
                echo '
                <h3>' . __('Create backups') . '</h3>
                <form id="module-backup-create-form" method="post" action="' . $p_url . '">
                <p>' . __('Choose languages to backup') . '</p>
                <table class="clear">
                <tr><th colspan="3"></th></tr>';
                $i=0;
                foreach($M->used_langs AS $name => $lang) {
                    $i++;
                    echo '
                    <tr class="line">
                    <td class="minimal">' . form::checkbox(array('langs[]') ,$lang, '', '', '', false) . '</td>
                    <td class="maximal">' . $name . '</td>
                    <td class="nowrap">' . $lang . '</td>
                    </tr>';
                }
                echo '
                </table>
                <div class="two-cols">
                <p class="col checkboxes-helpers">&nbsp;</p>
                <p class="col right">&nbsp;</p>
                </div>
                <p>
                <input type="submit" name="save" value="' . __('Backup') . '" />' . 
                form::hidden(array('modules[]'), $module) . 
                $core->formNonce() . 
                form::hidden(array('type'), $type) . 
                form::hidden(array('module'), $module) . 
                form::hidden(array('action'), 'module_create_backup') . 
                form::hidden(array('part'), 'module') . 
                form::hidden(array('tab'), 'module-backup') . 
                form::hidden(array('p'), 'translater') . '
                </p>
                </form>
                <p>&nbsp;</p>';
            }

            if (!empty($M->backups)) {
                echo 
                '<h3>' . __('List of backups') . '</h3>' . 
                '<form id="module-backup-edit-form" method="post" action="' . $p_url . '">' . 
                '<table class="clear">' . 
                '<tr>' . 
                '<th colspan="2">' . __('File') . '</th>' . 
                '<th>' . __('Date') . '</th>' . 
                '<th>' . __('Language') . '</th>' . 
                '<th>' . __('Size') . '</th>' . 
                '</tr>';
                $i=0;
                foreach($M->backups as $lang => $langs) {
                    foreach($langs as $file => $infos) {
                        $i++;
                        echo 
                        '<tr class="line">' . 
                        '<td class="minimal">' . form::checkbox(array('files[]'), $file, '', '', '', false) . '</td>' . 
                        '<td class="maximal">' . $file . '</td>' . 
                        '<td class="nowrap">' . 
                        dt::str(__('%Y-%m-%d %H:%M:%S'), $infos['time'], $core->blog->settings->system->blog_timezone) . 
                        '</td>' . 
                        '<td class="nowrap">' . $translater->isIsoCode($lang) . '</td>' . 
                        '<td class="nowrap">' . files::size($infos['size']) . '</td>' . 
                        '</tr>';
                    }
                }
                echo '
                </table>
                <div class="two-cols">
                <p class="col checkboxes-helpers">&nbsp;</p>
                <p class="col right">' . __('Selected backups action:') . ' ' . 
                form::combo('action', array(
                    __('Restore backups') => 'module_restore_backup',
                    __('Delete backups') => 'module_delete_backup')
                ) . '
                <input type="submit" name="save" value="' . __('ok') . '" />' . 
                form::hidden(array('modules[]'), $module) . 
                $core->formNonce() . 
                form::hidden(array('type'), $type) . 
                form::hidden(array('module'), $module) . 
                form::hidden(array('part'), 'module') . 
                form::hidden(array('tab'), 'module-backup') . 
                form::hidden(array('p'), 'translater') . '
                </p>
                </div>
                </form>
                <p>&nbsp;</p>';
            }

            echo '</div>';
        } // end if (!empty($M->used_langs) || !empty($M->backups)) {

        # Import/Export pack
        echo '<div class="multi-part" id="module-pack" title="' . __('Import/Export') . '">';

        # Import
        echo '
        <h3>' . __('Import') . '</h3>
        <form id="module-pack-import-form" method="post" action="' . $p_url . '" enctype="multipart/form-data">
        <p>' . __('Choose language package to import') . '<br />
        <input type="file" name="packfile" size="40"/></p>
        <p>
        <input type="submit" name="save" value="' . __('Import') . '" />' . 
        form::hidden(array('modules[]'), $module) . 
        $core->formNonce() . 
        form::hidden(array('type'), $type) . 
        form::hidden(array('module'), $module) . 
        form::hidden(array('action'), 'module_import_pack') . 
        form::hidden(array('part'), 'module') . 
        form::hidden(array('tab'), 'module-pack') . 
        form::hidden(array('p'), 'translater') . '
        </p>
        </form>
        <p>&nbsp;</p>';

        # Export
        if (!empty($M->used_langs)) {
            echo 
            '<h3>' . __('Export') . '</h3>' . 
            '<form id="module-pack-export-form" method="post" action="' . $p_url . '">' . 
            '<p>' . __('Choose languages to export') . '</p>' . 
            '<table class="clear">' . 
            '<tr><th colspan="3"></th></tr>';
            $i=0;
            foreach($M->used_langs AS $name => $lang) {
                $i++;
                echo 
                '<tr class="line">' . 
                '<td class="minimal">' . 
                form::checkbox(array('entries[]'), $lang, '', '', '', false) . 
                '</td>' . 
                '<td class="maximal">' . $name . '</td>' . 
                '<td class="nowrap">' . $lang . '</td>' . 
                '</tr>';
            }
            echo 
            '</table>' . 
            '<div class="two-cols">' . 
            '<p class="col checkboxes-helpers">&nbsp;</p>' . 
            '<p class="col right">&nbsp;</p>' . 
            '</div>' . 
            '<p>' . 
            '<input type="submit" name="save" value="' . __('Export') . '" />' . 
            form::hidden(array('modules[]'), $module) . 
            $core->formNonce() . 
            form::hidden(array('type'), $type) . 
            form::hidden(array('module'), $module) . 
            form::hidden(array('action'), 'module_export_pack') . 
            form::hidden(array('part'), 'module') . 
            form::hidden(array('tab'), 'module-pack') . 
            form::hidden(array('p'), 'translater') . 
            '</p>' . 
            '</form>' . 
            '<p>&nbsp;</p>';
        }
        echo '</div>';
    break;

    /**
     * Lang
     */
    case 'lang':
        # Existing langs
        if (empty($M->langs) || !isset($M->langs[$lang])) {
            break;
        }

        $iso = $M->langs[$lang];

        $i = 0;
        $sort_order = 'asc';
        $lines = $translater->getMsgs($module, $lang);

        # Sort array
        if (isset($_GET['sort']) && !empty($lines)) {
            $sort = explode(',', $_GET['sort']);
            $sort_by = $sort[0];
            $sort_order = isset($sort[1]) && $sort[1] == 'desc' ? 'asc' : 'desc';

            switch($sort_by) {
                case 'group':
                    foreach($lines AS $k => $v) {
                        $sort_list[] = $v['group'];
                    }
                break;

                case 'msgid':
                    foreach($lines AS $k => $v) {
                        $sort_list[] = strtolower($k);
                    }
                break;

                case 'file':
                    foreach($lines AS $k => $v) {
                        $file = array();
                        foreach($v['files'] as $fv) {
                            $file[] = empty($fv[0]) || empty($fv[1]) ? '' : $fv[0] . ($fv[1] /1000);
                        }
                        sort($file);
                        $sort_list[] = $file[0];
                    }
                break;

                case 'msgstr':
                    foreach($lines AS $k => $v) {
                        $sort_list[] = strtolower($v['msgstr']);
                    }
                break;

                default:
                    $sort_list = false;
                break;
            }
            if ($sort_list) {
                array_multisort(
                    $sort_list,
                    ($sort_order == 'asc' ? SORT_DESC : SORT_ASC),
                    SORT_STRING,
                    $lines
                );
            }
        }

        echo 
        '<div id="lang-form" title="' . $iso . '">' . 
        '<form id="lang-edit-form" method="post" action="' . $p_url . '">' . 
        '<table>' . 
        '<tr>' . 
        '<th><a href="' . $p_url . '&amp;part=lang&amp;module=' . $module . '&amp;type=' . $type . '&amp;lang=' . $lang . 
        '&amp;sort=group,' . $sort_order . '">' . __('Group') . '</a></th>' . 
        '<th><a href="' . $p_url . '&amp;part=lang&amp;module=' . $module . '&amp;type=' . $type . '&amp;lang=' . $lang . 
        '&amp;sort=msgid,' . $sort_order . '">' . __('String') . '</a></th>' . 
        '<th><a href="' . $p_url . '&amp;part=lang&amp;module=' . $module . '&amp;type=' . $type . '&amp;lang=' . $lang . 
        '&amp;sort=msgstr,' . $sort_order . '">' . __('Translation') . '</a></th>' . 
        '<th>' . __('Existing') . '</th>' . 
        '<th><a href="' . $p_url . '&amp;part=lang&amp;module=' . $module . '&amp;type=' . $type . '&amp;lang=' . $lang . 
        '&amp;sort=file,' . $sort_order . '">' . __('File') . '</a></th>' . 
        '</tr>';

        foreach ($lines AS $msgid => $rs) {
            $i++;
            $in_dc = ($rs['in_dc'] && $translater->parse_nodc);

            echo 
            '<tr class="line' . ($in_dc ? ' offline' : ' translaterline') . '">' . 

            '<td class="nowrap">' . 
            form::checkbox(array('entries[' . $i . '][check]'), 1) . ' ' . 
            form::combo(array('entries[' . $i . '][group]'),
                $allowed_groups,$rs['group'], '', '', $in_dc
            ) . 
            '</td>' . 

            '<td' . ('' != $translater->proposal_tool ? ' class="translatermsgid"' : '' ) . '>' . 
            html::escapeHTML($msgid) . '</td>' . 

            '<td class="nowrap translatertarget">' . 
            form::hidden(array('entries[' . $i . '][msgid]'), html::escapeHTML($msgid)) . 
            form::field(array('entries[' . $i . '][msgstr]'),
                48, 255, html::escapeHTML($rs['msgstr']), '', '', $in_dc) . 
            '</td>' . 

            '<td class="translatermsgstr">';
            $strin = array();
            foreach($rs['o_msgstrs'] AS $o_msgstr) {
                if (!isset($strin[$o_msgstr['msgstr']])) {
                    $strin[$o_msgstr['msgstr']] = array();
                }
                $strin[$o_msgstr['msgstr']][] = array('module' => $o_msgstr['module'], 'file' => $o_msgstr['file']);
            }
            foreach($strin as $k => $v) {
                echo '<div class="subtranslatermsgstr"><strong>' . html::escapeHTML($k) . '</strong><div class="strlist">';
                foreach($v as $str) {
                    echo '<i>' . html::escapeHTML($str['module'] . ' => ' . $str['file']) . '</i><br />';
                }
                echo '</div></div><br />';
            }
            echo 
            '</td>' . 

            '<td class="nowrap translatermsgfile">';
            if (empty($rs['files'][0])) {
                echo '&nbsp;';
            } elseif (count($rs['files']) == 1) {
                echo $rs['files'][0][0] . ' : ' . $rs['files'][0][1];
            } else {
                echo
                '<strong>' . sprintf(__('%s occurrences'), count($rs['files'])) . '</strong>' . 
                '<div class="strlist">';
                foreach($rs['files'] as $location) {
                    echo '<i>' . implode(' : ', $location) . '</i><br />';
                }
                echo '</div>';
            }
            echo
            '</td>' . 
            '</tr>';
        }

        $i++;
        echo 
        '<tr>' . 
        '<td class="nowrap">' . 
        form::checkbox(array('entries[' . $i . '][check]'), 1) . ' ' . 
        form::combo(array('entries[' . $i . '][group]'), $allowed_groups, 'main') . 
        '</td>' . 
        '<td class="">' . form::field(array('entries[' . $i . '][msgid]'), 48, 255, '') . '</td>' . 
        '<td class="nowrap">' . form::field(array('entries[' . $i . '][msgstr]'), 48, 255, '') . '</td>' . 
        '<td class="">&nbsp;</td>' . 
        '<td class="">&nbsp;</td>' . 
        '</tr>' . 
        '</table>' . 
        '<p>' . sprintf(__('Total of %s strings . '), $i-1) . '</p>' . 
        '<p class="col checkboxes-helpers"></p>' . 
        '<p class="col right">' . __('Change the group of the selected entries to:') . ' ' . 
        form::combo(array('multigroup'), $allowed_groups) . 
        '</p>' . 
        '<p>' . 
        '<input type="submit" name="save" value="' . __('Save') . '" />' . 
        $core->formNonce() . 
        form::hidden(array('lang'), $lang) . 
        form::hidden(array('type') ,$type) . 
        form::hidden(array('module'), $module) . 
        form::hidden(array('action'), 'update_lang') . 
        form::hidden(array('part'), 'lang') . 
        form::hidden(array('p'), 'translater') . 
        '</p>' . 
        '</form>' . 
        '<p>&nbsp;</p>' . 
        '</div>';
    break;

    /**
     * Import/Export (Pack)
     */
    case 'pack':
        # Import
        echo '
        <div class="multi-part" id="pack-import" title="' . __('Import') . '">
        <form id="pack-import-form" method="post" action="' . $p_url . '" enctype="multipart/form-data">
        <p>' . __('Choose language package to import') . '<br />
        <input type="file" name="packfile" size="40"/></p>
        <p>
        <input type="submit" name="save" value="' . __('Import') . '" />';
        $i=0;
        foreach($translater->listModules() AS $k => $v) {
            if ($translater->hide_default && (
                in_array($k,dcTranslater::$default_dotclear_modules['plugin']) || 
                in_array($k,dcTranslater::$default_dotclear_modules['theme']))) {
                continue;
            }
            echo form::hidden(array('modules[]'), $k);$i++;
        }
        echo 
        $core->formNonce() . 
        form::hidden(array('type'), $type) . 
        form::hidden(array('module'), $module) . 
        form::hidden(array('action'), 'import_pack') . 
        form::hidden(array('part'), 'pack') . 
        form::hidden(array('tab'), 'pack-import') . 
        form::hidden(array('p'), 'translater') . '
        </p>
        </form>
        </div>';

        # Export
        echo '
        <div class="multi-part" id="pack-export" title="' . __('Export') . '">
        <form id="pack-export-form" method="post" action="' . $p_url . '">
        <p>' . __('Choose modules to export') . '</p>
        <table class="clear">
        <tr><th colspan="2">' . __('Modules') . '</th><th>' . __('Languages') . '</th></tr>';
        $i=0;
        $langs_list = array();

        foreach($translater->listModules() AS $k => $v) {
            if ($translater->hide_default && (
                in_array($k,dcTranslater::$default_dotclear_modules['plugin']) || 
                in_array($k,dcTranslater::$default_dotclear_modules['theme']))) {
                continue;
            }

            $info_lang = $translater->listLangs($k);
            if (!is_array($info_lang) || 1 > count($info_lang)) {
                continue;
            }

            $i++;
            $langs_list = array_merge($langs_list, $info_lang);

            echo '
            <tr class="line">
            <td class="minimal">' . form::checkbox(array('modules[]'), $k, '', '', '', false) . '</td>
            <td class="nowrap">' . $v['name'] . '</td>
            <td class="maximal">' . implode(', ', $info_lang) . '</td>
            </tr>';
        }

        echo '
        </table>
        <p>' . __('Choose languages to export') . '</p>
        <table class="clear">
        <tr><th colspan="2">' . __('Languages') . '</th><th>' . __('Code') . '</th></tr>';
        $i=0;
        foreach($langs_list AS $k => $v) {
            $i++;
            echo '
            <tr class="line">
            <td class="minimal">' . form::checkbox(array('entries[]'), $k, '', '', '', false) . '</td>
            <td class="nowwrap">' . $v . '</td>
            <td class="maximal">' . $k . '</td>
            </tr>';
        }
        echo '
        </table>
        <div class="two-cols">
        <p class="col checkboxes-helpers"></p>
        <p class="col right">&nbsp;</p>
        </div>
        <p>
        <input type="submit" name="save" value="' . __('Export') . '" />' . 
        $core->formNonce() . 
        form::hidden(array('type'), $type) . 
        form::hidden(array('module'), $module) . 
        form::hidden(array('action'), 'export_pack') . 
        form::hidden(array('part'), 'pack') . 
        form::hidden(array('tab'), 'pack-export') . 
        form::hidden(array('p'), 'translater') . '
        </p>
        </form>
        </div>';
    break;
}

dcPage::helpBlock('translater');

echo '
</body></html>';