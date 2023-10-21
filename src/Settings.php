<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

/**
 * @brief       translater settings helper.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Settings
{
    /**
     * Show tranlsater button on plugins list.
     *
     * @var     bool    $plugin_menu
     */
    public readonly bool $plugin_menu;

    /**
     * Show tranlsater button on themes list.
     *
     * @var     bool   $theme_menu
     */
    public readonly bool $theme_menu;

    /**
     * Create language backup on save.
     *
     * @var     bool    $backup_auto
     */
    public readonly bool $backup_auto;

    /**
     * Backups number limit.
     *
     * @var     int     $backup_limit
     */
    public readonly int $backup_limit;

    /**
     * Backup main folder.
     *
     * @var     string  $backup_folder
     */
    public readonly string $backup_folder;

    /**
     * Default ui start page.
     *
     * @var     string  $start_page
     */
    public readonly string $start_page;

    /**
     * Write .lang.php file (deprecated).
     *
     * @var     bool    $write_langphp
     */
    public readonly bool $write_langphp;

    /**
     * Scan also template files for translations.
     *
     * @var     bool    $scan_tpl
     */
    public readonly bool $scan_tpl;

    /**
     * Disable translation of know dotclear strings.
     *
     * @var     bool    $parse_nodc
     */
    public readonly bool $parse_nodc;

    /**
     * Hide official modules.
     *
     * @var     bool    $hide_default
     */
    public readonly bool $hide_default;

    /**
     * Add comment to translations files.
     *
     * @var     bool    $parse_comment
     */
    public readonly bool $parse_comment;

    /**
     * Parse user info to translations files.
     *
     * @var     bool    $parse_user
     */
    public readonly bool $parse_user;

    /**
     * User infos to parse.
     *
     * @var     string  $parse_userinfo
     */
    public readonly string $parse_userinfo;

    /**
     * Overwrite existing languages on import.
     *
     * @var     bool    $import_overwrite
     */
    public readonly bool $import_overwrite;

    /**
     * Filename of exported lang.
     *
     * @var     string  $export_filename
     */
    public readonly string $export_filename;

    /**
     * Constructor set up plugin settings
     */
    public function __construct()
    {
        $s = My::settings();

        $this->plugin_menu      = (bool) ($s->get('plugin_menu') ?? false);
        $this->theme_menu       = (bool) ($s->get('theme_menu') ?? false);
        $this->backup_auto      = (bool) ($s->get('backup_auto') ?? false);
        $this->backup_limit     = (int) ($s->get('backup_limit') ?? 20);
        $this->backup_folder    = (string) ($s->get('backup_folder') ?? 'module');
        $this->start_page       = (string) ($s->get('start_page') ?? '-');
        $this->write_langphp    = (bool) ($s->get('write_langphp') ?? false);
        $this->scan_tpl         = (bool) ($s->get('scan_tpl') ?? true);
        $this->parse_nodc       = (bool) ($s->get('parse_nodc') ?? true);
        $this->hide_default     = (bool) ($s->get('hide_default') ?? true);
        $this->parse_comment    = (bool) ($s->get('parse_comment') ?? false);
        $this->parse_user       = (bool) ($s->get('parse_user') ?? false);
        $this->parse_userinfo   = (string) ($s->get('parse_userinfo') ?? 'displayname, email');
        $this->import_overwrite = (bool) ($s->get('import_overwrite') ?? false);
        $this->export_filename  = (string) ($s->get('export_filename') ?? 'type-module-l10n-timestamp');
    }

    /**
     * Get a setting.
     *
     * @return  null|bool|int|string
     */
    public function getSetting(string $key): mixed
    {
        return $this->{$key} ?? null;
    }

    /**
     * Overwrite a plugin settings (in db).
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
     * List defined settings keys.
     *
     * @return  array<string, null|bool|int|string>     The settings keys
     */
    public function listSettings(): array
    {
        return get_object_vars($this);
    }
}
