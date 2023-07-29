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

class Settings
{
    // Show tranlsater button on plugins list
    public readonly bool $plugin_menu;

    // Show tranlsater button on themes list
    public readonly bool $theme_menu;

    // Create language backup on save
    public readonly bool $backup_auto;

    // Backups number limit
    public readonly int $backup_limit;

    // Backup main folder
    public readonly string $backup_folder;

    // Default ui start page
    public readonly string $start_page;

    // Write .lang.php file (deprecated)
    public readonly bool $write_langphp;

    // Scan also template files for translations
    public readonly bool $scan_tpl;

    // Disable translation of know dotclear strings
    public readonly bool $parse_nodc;

    // Hide official modules
    public readonly bool $hide_default;

    // Add comment to translations files
    public readonly bool $parse_comment;

    // Parse user info to translations files
    public readonly bool $parse_user;

    // User infos to parse
    public readonly string $parse_userinfo;

    // Overwrite existing languages on import
    public readonly bool $import_overwrite;

    // Filename of exported lang
    public readonly string $export_filename;

    /**
     * Constructor set up plugin settings
     */
    public function __construct()
    {
        $s = My::settings();

        $this->plugin_menu      = (bool) ($s?->get('plugin_menu') ?? false);
        $this->theme_menu       = (bool) ($s?->get('theme_menu') ?? false);
        $this->backup_auto      = (bool) ($s?->get('backup_auto') ?? false);
        $this->backup_limit     = (int) ($s?->get('backup_limit') ?? 20);
        $this->backup_folder    = (string) ($s?->get('backup_folder') ?? 'module');
        $this->start_page       = (string) ($s?->get('start_page') ?? '-');
        $this->write_langphp    = (bool) ($s?->get('write_langphp') ?? false);
        $this->scan_tpl         = (bool) ($s?->get('scan_tpl') ?? true);
        $this->parse_nodc       = (bool) ($s?->get('parse_nodc') ?? true);
        $this->hide_default     = (bool) ($s?->get('hide_default') ?? true);
        $this->parse_comment    = (bool) ($s?->get('parse_comment') ?? false);
        $this->parse_user       = (bool) ($s?->get('parse_user') ?? false);
        $this->parse_userinfo   = (string) ($s?->get('parse_userinfo') ?? 'displayname, email');
        $this->import_overwrite = (bool) ($s?->get('import_overwrite') ?? false);
        $this->export_filename  = (string) ($s?->get('export_filename') ?? 'type-module-l10n-timestamp');
    }

    public function getSetting(string $key): mixed
    {
        return $this->{$key} ?? null;
    }

    /**
     * Overwrite a plugin settings (in db)
     *
     * @param   string  $key    The setting ID
     * @param   mixed   $value  The setting value
     *
     * @return  bool True on success
     */
    public function writeSetting(string $key, mixed $value): bool
    {
        if (property_exists($this, $key) && settype($value, gettype($this->{$key})) === true) {
            My::settings()->drop($key);
            My::settings()->put($key, $value, gettype($this->{$key}), '', true, true);

            return true;
        }

        return false;
    }

    /**
     * List defined settings keys
     *
     * @return  array   The settings keys
     */
    public function listSettings(): array
    {
        return get_object_vars($this);
    }
}
