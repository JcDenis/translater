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

/**
 * Plugin definitions
 */
class My
{
    /**
     * This module id
     */
    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    /**
     * This module name
     */
    public static function name(): string
    {
        return __((string) dcCore::app()->plugins->moduleInfo(self::id(), 'name'));
    }

    /**
     * List of allowed backup folder
     */
    public static function backupFoldersCombo(): array
    {
        return [
            __('locales folders of each module') => 'module',
            __('plugins folder root')            => 'plugin',
            __('public folder root')             => 'public',
            __('cache folder of Dotclear')       => 'cache',
            __('locales folder of translater')   => self::id(),
        ];
    }

    /**
     * List of possible home tab of the plugin
     */
    public static function startPageCombo()
    {
        return [
            __('Plugins') => 'plugin',
            __('Themes')  => 'theme',
            __('Home')    => '-',
        ];
    }

    /**
     * List of place of tranlsations
     */
    public static function l10nGroupsCombo(): array
    {
        $groups = [
            'main', 'public', 'theme', 'admin', 'date', 'error',
        ];

        return array_combine($groups, $groups);
    }

    /**
     * List of user info can be parsed
     */
    public static function defaultUserInformations(): array
    {
        return [
            'firstname', 'displayname', 'name', 'email', 'url',
        ];
    }

    /**
     * List of distributed plugins and themes
     */
    public static function defaultDistribModules(string $type): array
    {
        $types = [
            'plugin' => explode(',', DC_DISTRIB_PLUGINS),
            'theme'  => explode(',', DC_DISTRIB_THEMES),
        ];

        return $types[$type] ?? [];
    }

    public static function defaultSettings(): array
    {
        return [
            // Show tranlsater button on plugins list
            'plugin_menu' => false,
            // Show tranlsater button on themes list
            'theme_menu' => false,
            // Create language backup on save
            'backup_auto' => false,
            // Backups number limit
            'backup_limit' => 20,
            // Backup main folder
            'backup_folder' => 'module',
            // Default ui start page
            'start_page' => '-',
            // Write .lang.php file (deprecated)
            'write_langphp' => false,
            // Scan also template files for translations
            'scan_tpl' => true,
            // Disable translation of know dotclear strings
            'parse_nodc' => true,
            // Hide official modules
            'hide_default' => true,
            // Add comment to translations files
            'parse_comment' => false,
            // Parse user info to translations files
            'parse_user' => false,
            // User infos to parse
            'parse_userinfo' => 'displayname, email',
            // Overwrite existing languages on import
            'import_overwrite' => false,
            // Filename of exported lang
            'export_filename' => 'type-module-l10n-timestamp',
            // Default service for external proposal tool
            'proposal_tool' => 'google',
            // Default lang for external proposal tool
            'proposal_lang' => 'en',
        ];
    }
}
