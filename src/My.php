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
use Dotclear\Module\MyPlugin;

/**
 * Plugin definitions
 */
class My extends MyPlugin
{
    /** @var    string  Locales folder name */
    public const LOCALES_FOLDER = 'locales';

    public static function checkCustomContext(int $context): ?bool
    {
        return dcCore::app()->auth->isSuperAdmin();
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
    public static function startPageCombo(): array
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
}
