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

class dcTranslaterDefaultSettings
{
    /** @var boolean Show tranlsater button on plugins list */
    public $plugin_menu = false;
    /** @var boolean Show tranlsater button on themes list */
    public $theme_menu = false;
    /** @var boolean Create language backup on save */
    public $backup_auto = false;
    /** @var integer Backups number limit */
    public $backup_limit = 20;
    /** @var string Backup main folder */
    public $backup_folder = 'module';
    /** @var string Default ui start page */
    public $start_page = '-';
    /** @var boolean Write .lang.php file (deprecated) */
    public $write_langphp = false;
    /** @var boolean SCan also template files for translations */
    public $scan_tpl = true;
    /** @var boolean Disable translation of know dotclear strings */
    public $parse_nodc = true;
    /** @var boolean Hide official modules */
    public $hide_default = true;
    /** @var boolean Add comment to translations files */
    public $parse_comment = false;
    /** @var boolean Parse user info to translations files */
    public $parse_user = false;
    /** @var string User infos to parse */
    public $parse_userinfo = 'displayname, email';
    /** @var boolean Overwrite existing languages on import */
    public $import_overwrite = false;
    /** @var string Filename of exported lang */
    public $export_filename = 'type-module-l10n-timestamp';
    /** @var string Default service for external proposal tool */
    public $proposal_tool = 'google';
    /** @var string Default lang for external proposal tool */
    public $proposal_lang = 'en';

    /**
     * get default settings
     *
     * @return array Settings key/value pair
     */
    public static function getDefaultSettings()
    {
        return get_class_vars('dcTranslaterDefaultSettings');
    }
}

/**
 * Translater tools.
 */
class dcTranslater extends dcTranslaterDefaultSettings
{
    /** @var array $allowed_backup_folders List of allowed backup folder */
    public static $allowed_backup_folders = [];

    /** @var array $allowed_l10n_groups List of place of tranlsations */
    public static $allowed_l10n_groups = [
        'main', 'public', 'theme', 'admin', 'date', 'error',
    ];

    /** @var array $allowed_user_informations List of user info can be parsed */
    public static $allowed_user_informations = [
        'firstname', 'displayname', 'name', 'email', 'url',
    ];

    /** @var array $default_distrib_modules List of distributed plugins and themes */
    public static $default_distrib_modules = ['plugin' => [], 'theme' => []];

    /** @var array $modules List of modules we could work on */
    private $modules = [];

    /**
     * translater instance
     *
     * @param boolean $full Also load modules
     */
    public function __construct(bool $full = true)
    {
        $this->loadSettings();

        if ($full) {
            $this->loadModules();
        }

        self::$allowed_backup_folders = [
            __('locales folders of each module') => 'module',
            __('plugins folder root')            => 'plugin',
            __('public folder root')             => 'public',
            __('cache folder of Dotclear')       => 'cache',
            __('locales folder of translater')   => basename(dirname(__DIR__)),
        ];
        self::$default_distrib_modules = [
            'plugin' => explode(',', DC_DISTRIB_PLUGINS),
            'theme'  => explode(',', DC_DISTRIB_THEMES),
        ];
    }

    /// @name settings methods
    //@{
    /**
     * Load settings from db
     */
    public function loadSettings(): void
    {
        foreach ($this->getDefaultSettings() as $key => $value) {
            $this->$key = dcCore::app()->blog->settings->get(basename(dirname(__DIR__)))->get($key);

            try {
                settype($this->$key, gettype($value));
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Write settings to db
     *
     * @param  boolean $overwrite Overwrite existing settings
     */
    public function writeSettings($overwrite = true): void
    {
        foreach ($this->getDefaultSettings() as $key => $value) {
            dcCore::app()->blog->settings->get(basename(dirname(__DIR__)))->drop($key);
            dcCore::app()->blog->settings->get(basename(dirname(__DIR__)))->put($key, $this->$key, gettype($value), '', true, true);
        }
    }

    /**
     * Upgrade plugin
     *
     * @return bool Upgrade done
     */
    public function growUp()
    {
        $current = dcCore::app()->getVersion(basename(dirname(__DIR__)));

        // use short settings id
        if ($current && version_compare($current, '2022.12.22', '<')) {
            $record = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'translater' "
            );
            while ($record->fetch()) {
                if (preg_match('/^translater_(.*?)$/', $record->setting_id, $match)) {
                    $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                    $cur->setting_id = $this->{$match[1]} = $match[1];
                    $cur->setting_ns = basename(dirname(__DIR__));
                    $cur->update(
                        "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'translater' " .
                        'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                    );
                }
            }

            return true;
        }

        return false;
    }
    //@}

    /// @name modules methods
    //@{
    /**
     * Load array of modules infos by type of modules
     */
    private function loadModules(): void
    {
        $this->modules['theme'] = $this->modules['plugin'] = [];

        $themes = new dcThemes();
        $themes->loadModules(dcCore::app()->blog->themes_path, null);

        $list = [
            'theme'  => $themes->getModules(),
            'plugin' => dcCore::app()->plugins->getModules(),
        ];
        foreach ($list as $type => $modules) {
            foreach ($modules as $id => $info) {
                if (!$info['root_writable']) {
//                    continue;
                }
                $info['id']                = $id;
                $info['type']              = $type;
                $this->modules[$type][$id] = new dcTranslaterModule($this, $info);
            }
        }
    }

    /**
     * Return array of modules infos by type of modules
     *
     * @param  string $type The modules type
     *
     * @return array        The list of modules infos
     */
    public function getModules(string $type = ''): array
    {
        return in_array($type, ['plugin', 'theme']) ?
            $this->modules[$type] :
            array_merge($this->modules['theme'], $this->modules['plugin']);
    }

    /**
     * Return module class of a particular module for a given type of module
     *
     * @param  string   $type       The module type
     * @param  string   $id         The module id
     *
     * @return dcTranslaterModule   The dcTranslaterModule instance
     */
    public function getModule(string $type, string $id)
    {
        if (!isset($this->modules[$type][$id])) {
            throw new Exception(
                sprintf(__('Failed to find module %s'), $id)
            );
        }

        return $this->modules[$type][$id];
    }

    /**
     * Return module class of a particular module for a given type of module
     *
     * @param  dcTranslaterModule   $module     dcTranslaterModule instance
     * @param  string               $lang       The lang iso code
     *
     * @return dcTranslaterLang                 dcTranslaterLang instance or false
     */
    public function getLang(dcTranslaterModule $module, string $lang)
    {
        if (!l10n::isCode($lang)) {
            throw new Exception(
                sprintf(__('Failed find language %s'), $lang)
            );
        }

        return new dcTranslaterLang($module, $lang);
    }
    //@}

    /// @name helper methods
    //@{
    /**
     * Scan recursively a folder and return files and folders names
     *
     * @param  string $path The path to scan
     * @param  string $dir  Internal recursion
     * @param  array  $res  Internal recursion
     * @return array        List of path
     */
    public static function scandir(string $path, string $dir = '', array $res = []): array
    {
        $path = (string) path::real($path, false);
        if (empty($path) || !is_dir($path) || !is_readable($path)) {
            return [];
        }

        $files = files::scandir($path);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            if (is_dir($path . '/' . $file)) {
                $res[] = $file;
                $res   = self::scanDir($path . '/' . $file, $dir . '/' . $file, $res);
            } else {
                $res[] = empty($dir) ? $file : $dir . '/' . $file;
            }
        }

        return $res;
    }

    /**
     * Encode a string
     *
     * @param  string $str The string to encode
     * @return string      The encoded string
     */
    public static function encodeMsg(string $str): string
    {
        return text::toUTF8(stripslashes(trim($str)));
    }

    /**
     * Clean a po string
     *
     * @param  string  $string  The string to clean
     * @param  boolean $reverse Un/escape string
     * @return string           The cleaned string
     */
    public static function poString(string $string, bool $reverse = false): string
    {
        if ($reverse) {
            $smap = ['"', "\n", "\t", "\r"];
            $rmap = ['\\"', '\\n"' . "\n" . '"', '\\t', '\\r'];

            return trim((string) str_replace($smap, $rmap, $string));
        }
        $smap = ['/"\s+"/', '/\\\\n/', '/\\\\r/', '/\\\\t/', '/\\\"/'];
        $rmap = ['', "\n", "\r", "\t", '"'];

        return trim((string) preg_replace($smap, $rmap, $string));
    }

    /**
     * Try if a file is a .po file
     *
     * @param  string  $file The path to test
     * @return boolean       Success
     */
    public static function isPoFile(string $file): bool
    {
        return files::getExtension($file) == 'po';
    }

    /**
     * Try if a file is a .lang.php file
     *
     * @param  string  $file The path to test
     * @return boolean       Success
     */
    public static function isLangphpFile(string $file): bool
    {
        return files::getExtension($file) == 'php' && stristr($file, '.lang.php');
    }

    /**
     * Check limit number of backup for a module
     *
     * @param  string  $id     The module id
     * @param  string  $root   The backups root
     * @param  integer $limit  The backups limit
     * @param  boolean $throw  Silently failed
     * @return boolean         True if limit is riched
     */
    public static function isBackupLimit(string $id, string $root, int $limit = 10, bool $throw = false): bool
    {
        if (!$limit) {
            return false;
        }

        $count = 0;
        foreach (self::scandir($root) as $file) {
            if (!is_dir($root . '/' . $file)
                && preg_match('/^(l10n-' . $id . '(.*?).bck.zip)$/', $root)
            ) {
                $count++;
            }
        }

        # Limite exceed
        if ($count >= $limit) {
            if ($throw) {
                throw new Exception(
                    sprintf(__('Limit of %s backups for module %s exceed'), $limit, $id)
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Extract messages from a php contents
     *
     * support plurals
     *
     * @param  string $content The contents
     * @param  string $func    The function name
     * @return array           The messages
     */
    public static function extractPhpMsgs(string $content, string $func = '__'): array
    {
        $duplicate = $final_strings = $lines = [];
        // split content by line to combine match/line on the end
        $content = str_replace("\r\n", "\n", $content);
        $o       = 0;
        $parts   = explode("\n", $content);
        foreach ($parts as $li => $part) {
            $m = explode($func . '(', $part);
            for ($i = 1; $i < count($m); $i++) {
                $lines[$o] = $li + 1;
                $o++;
            }
        }
        // split content by translation function
        $parts = explode($func . '(', $content);
        // remove fisrt element from array
        array_shift($parts);
        // walk through parts
        $p = 0;
        foreach ($parts as $part) {
            // should start with quote
            if (!in_array(substr($part, 0, 1), ['"', "'"])) {
                $p++;

                continue;
            }
            // put back first parenthesis
            $part = '(' . $part;
            // find pairs of parenthesis
            preg_match_all("/\((?:[^\)\(]+|(?R))*+\)/s", $part, $subparts);
            // find quoted strings (single or double)
            preg_match_all("/\'(?:[^\']+)\'|\"(?:[^\"]+)\"/s", $subparts[0][0], $strings);
            // strings exist
            if (!empty($strings[0])) {
                // remove quotes
                $strings[0] = array_map(function ($v) { return substr($v, 1, -1);}, $strings[0]);
                // filter duplicate strings (only check first string for plurals form)
                if (!in_array($strings[0][0], $duplicate)) {
                    // fill final array
                    $final_strings[] = [$strings[0], $lines[$p]];
                    $duplicate[]     = $strings[0][0];
                }
            }
            $p++;
        }

        return $final_strings;
    }

    /**
     * Extract messages from a tpl contents
     *
     * @param  string $content The contents
     * @param  string $func    The function name
     * @return array           The messages
     */
    public static function extractTplMsgs(string $content, string $func = 'tpl:lang'): array
    {
        $duplicate = $final_strings = $lines = [];
        // split content by line to combine match/line on the end
        $content = str_replace("\r\n", "\n", $content);
        $o       = 0;
        $parts   = explode("\n", $content);
        foreach ($parts as $li => $part) {
            $m = explode('{{' . $func . ' ', $part);
            for ($i = 1; $i < count($m); $i++) {
                $lines[$o] = $li + 1;
                $o++;
            }
        }
        // split content by translation function
        if (!preg_match_all('/\{\{' . preg_quote($func) . '\s([^}]+)\}\}/', $content, $parts)) {
            return $final_strings;
        }
        // walk through parts
        $p = 0;
        foreach ($parts[1] as $part) {
            // strings exist
            if (!empty($part)) {
                // filter duplicate strings
                if (!in_array($part, $duplicate)) {
                    // fill final array
                    $final_strings[] = [[$part], $lines[$p]];
                    $duplicate[]     = $part;
                }
            }
            $p++;
        }

        return $final_strings;
    }
    //@}
}
