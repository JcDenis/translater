<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       translater My helper.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * Locales folder name.
     *
     * @var     string  LOCALES_FOLDER
     */
    public const LOCALES_FOLDER = 'locales';

    public static function checkCustomContext(int $context): ?bool
    {
        // Limit to super admin
        return match($context) {
            self::MODULE => App::auth()->isSuperAdmin(),
            default      => null,
        };
    }

    /**
     * List of allowed backup folder.
     *
     * @return  array<string, string>
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
     * List of possible home tab of the plugin.
     *
     * @return  array<string, string>
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
     * List of place of tranlsations.
     *
     * @return  array<string, string>
     */
    public static function l10nGroupsCombo(): array
    {
        $groups = [
            'main', 'public', 'theme', 'admin', 'date', 'error',
        ];

        return array_combine($groups, $groups);
    }

    /**
     * List of user info can be parsed.
     *
     * @return  array<int, string>
     */
    public static function defaultUserInformations(): array
    {
        return [
            'firstname', 'displayname', 'name', 'email', 'url',
        ];
    }

    /**
     * List of distributed plugins and themes.
     *
     * @param   string  $type   The modules type
     *
     * @return  array<int, string>
     */
    public static function defaultDistribModules(string $type): array
    {
        $types = [
            'plugin' => explode(',', App::config()->distributedPlugins()),
            'theme'  => explode(',', App::config()->distributedThemes()),
        ];

        return $types[$type] ?? [];
    }
}
