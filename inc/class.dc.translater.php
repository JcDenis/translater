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

/**
 * Translater tools.
 */
class dcTranslater
{
    /** @var dCore dcCore instance */
    public $core;

    /** @var array $default_settings Plugins default settings */
    private static $default_settings = [
        'plugin_menu' => [
            'id' => 'translater_plugin_menu',
            'value' => 0,
            'type' => 'boolean',
            'label' => 'Put an link in plugins page'
        ],
        'theme_menu' => [
            'id' => 'translater_theme_menu',
            'value' => 0,
            'type' => 'boolean',
            'label' => 'Put a link in themes page'
        ],
        'backup_auto' => [
            'id' => 'translater_backup_auto',
            'value' => 1,
            'type' => 'boolean',
            'label' => 'Make a backup of languages old files when there are modified'
        ],
        'backup_limit' => [
            'id' => 'translater_backup_limit',
            'value' => 20,
            'type' => 'string',
            'label' => 'Maximum backups per module'
        ],
        'backup_folder' => [
            'id' => 'translater_backup_folder',
            'value' => 'module',
            'type' => 'string',
            'label' => 'In which folder to store backups'
        ],
        'start_page' => [
            'id' => 'translater_start_page',
            'value' => '-',
            'type' => 'string',
            'label' => 'Page to start on'
        ],
        'write_langphp' => [
            'id' => 'translater_write_langphp',
            'value' => 0,
            'type' => 'boolean',
            'label' => 'Write .lang.php languages files'
        ],
        'scan_tpl' => [
            'id' => 'translater_scan_tpl',
            'value' => 0,
            'type' => 'boolean',
            'label' => 'Translate strings of templates files'
        ],
        'parse_nodc' => [
            'id' => 'translater_parse_nodc',
            'value' => 1,
            'type' => 'boolean',
            'label' => 'Translate only untranslated strings of Dotclear',
        ],
        'hide_default' => [
            'id' => 'translater_hide_default',
            'value' => 1,
            'type' => 'boolean',
            'label' => 'Hide default modules of Dotclear',
        ],
        'parse_comment' => [
            'id' => 'translater_parse_comment',
            'value' => 1,
            'type' => 'boolean',
            'label' => 'Write comments and strings informations in lang files'
        ],
        'parse_user' => [
            'id' => 'translater_parse_user',
            'value' => 1,
            'type' => 'boolean',
            'label' => 'Write inforamtions about author in lang files'
        ],
        'parse_userinfo' => [
            'id' => 'translater_parse_userinfo',
            'value' => 'displayname, email',
            'type' => 'string',
            'label' => 'Type of informations about user to write'
        ],
        'import_overwrite' => [
            'id' => 'translater_import_overwrite',
            'value' => 0,
            'type' => 'boolean',
            'label' => 'Overwrite existing languages when import packages'
        ],
        'export_filename' => [
            'id' => 'translater_export_filename',
            'value' => 'type-module-l10n-timestamp',
            'type' => 'string',
            'label' => 'Name of files of exported package'
        ],
        'proposal_tool' => [
            'id' => 'translater_proposal_tool',
            'value' => 'google',
            'type' => 'string',
            'label' => 'Id of default tool for proposed translation'
        ],
        'proposal_lang' => [
            'id' => 'translater_proposal_lang',
            'value' => 'en',
            'type' => 'string',
            'label' => 'Default source language for proposed translation'
        ]
    ];

    /** @var array $allowed_backup_folders List of allowed backup folder */
    public static $allowed_backup_folders = [];

    /** @var array $allowed_l10n_groups List of place of tranlsations */
    public static $allowed_l10n_groups = [
        'main', 'public', 'theme', 'admin', 'date', 'error'
    ];

    /** @var array $allowed_user_informations List of user info can be parsed */
    public static $allowed_user_informations = [
        'firstname', 'displayname', 'name', 'email', 'url'
    ];

    /** @var array $default_distrib_modules List of distributed plugins and themes */
    public static $default_distrib_modules = ['plugin' => [], 'theme' => []];

    /** @var array $modules List of modules we could work on */
    private $modules = [];

    /**
     * translater instance
     * 
     * @param dcCore $core  dcCore instance
     * @param boolean $core Also load modules
     */
    public function __construct(dcCore $core, bool $full = true)
    {
        $this->core = $core;
        $core->blog->settings->addNamespace('translater');

        if ($full) {
            $this->loadModules();
        }

        self::$allowed_backup_folders = [
            __('locales folders of each module') => 'module',
            __('plugins folder root')            => 'plugin',
            __('public folder root')             => 'public',
            __('cache folder of Dotclear')       => 'cache',
            __('locales folder of translater')   => 'translater'
        ];
        self::$default_distrib_modules = [
            'plugin' => explode(',', DC_DISTRIB_PLUGINS), 
            'theme'  => explode(',', DC_DISTRIB_THEMES)
        ];
    }

    /// @name settings methods
    //@{
    /**
     * Get array of default settings
     * 
     * @return array All default settings
     */
    public function getDefaultSettings(): array
    {
        return self::$default_settings;
    }

    /**
     * Get a setting according to default settings list
     * 
     * @param  string $id The settings short id
     * @return mixed     The setting value if exists or null
     */
    public function getSetting(string $id)
    {  
        return array_key_exists($id, self::$default_settings) ? 
            $this->core->blog->settings->translater->get(self::$default_settings[$id]['id']) : '';
    }

    /**
     * Magic getSetting
     */
    public function __get($id)
    {
        return $this->getSetting($id);
    }

    /**
     * Set a setting according to default settings list
     * 
     * @param string $id        The setting short id
     * @param mixed  $value     The setting value
     * @param mixed  $overwrite Overwrite settings if exists
     * @return boolean          Success
     */
    public function setSetting(string $id, $value, $overwrite = true): bool
    {
        if (!array_key_exists($id, self::$default_settings)) {
            return false;
        }
        $s = self::$default_settings[$id];
        $this->core->blog->settings->translater->drop($s['id']);
        $this->core->blog->settings->translater->put($s['id'], $value, $s['type'], $s['label'], $overwrite, true);
        return true;
    }

    /**
     * Magic setSetting
     */
    public function __set($id, $value)
    {
        return $this->setSetting($id, $value);
    }
    //@}

    /// @name modules methods
    //@{
    /** 
     * Load array of modules infos by type of modules
     */
    private function loadModules()
    {
        $this->modules['theme'] = $this->modules['plugin'] = [];

        $themes = new dcThemes($this->core);
        $themes->loadModules($this->core->blog->themes_path, null);

        $list = [
            'theme'  => $themes->getModules(),
            'plugin' => $this->core->plugins->getModules()
        ];
        foreach($list as $type => $modules) {
            foreach($modules as $id => $info) {
                if (!$info['root_writable']) {
//                    continue;
                }
                $info['id'] = $id;
                $info['type'] = $type;
                $this->modules[$type][$id] = new dcTranslaterModule($this, $info);
            }
        }
    }

    /** 
     * Return array of modules infos by type of modules
     * 
     * @param  string $type The modules type
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
     * @param  string $type       The module type
     * @param  string $id         The module id
     * @return dcTranslaterModule The dcTranslaterModule instance
     */
    public function getModule(string $type, string $id)
    {
        if (!isset($this->modules[$type][$id])) {
            throw new Exception(
                sprintf(__('Failed to find module %s'), $id)
            );
            return false;
        }
        return $this->modules[$type][$id];
    }

    /** 
     * Return module class of a particular module for a given type of module
     * 
     * @param  string $module   dcTranslaterModule instance
     * @param  string $lang     The lang iso code
     * @return dcTranslaterLang dcTranslaterLang instance or false
     */
    public function getLang(dcTranslaterModule $module, string $lang)
    {
        if (!l10n::isCode($lang)) {
            throw new Exception(
                sprintf(__('Failed find language %s'), $lang)
            );
            return false;
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
    public static function scandir(string $path, string $dir = '', array $res = [])
    {
        $path = path::real($path, false);
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $files = files::scandir($path);
        foreach($files AS $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            if (is_dir($path . '/' . $file)) {
                $res[] = $file;
                $res = self::scanDir($path . '/' . $file, $dir . '/' . $file, $res);
            } else {
                $res[] = empty($dir) ? $file : $dir . '/' . $file;
            }
        }
        return $res;
    }

    /* Try if a file is a .lang.php file */
    public static function isLangphpFile($file)
    {
        return files::getExtension($file) == 'php' && stristr($file, '.lang.php');
    }

    /* Try if a file is a .po file */
    public static function isPoFile($file)
    {
        return files::getExtension($file) == 'po';
    }

    /**
     * Check limit number of backup for a module
     * 
     * @param  string  $root   The backups root
     * @param  string  $limit  The backups limit
     * @param  boolean $throw  Silently failed
     * @return boolean         True if limit is riched
     */
    public static function isBackupLimit(string $root, int $limit = 10, bool $throw = false): bool
    {
        $count = 0;
        foreach(self::scandir($root) AS $file) {
            if (!is_dir($root . '/' . $file)
                && preg_match('/^(l10n-'. $id . '(.*?).bck.zip)$/', $root)
            ) {
                $count++;
            }
        }

        # Limite exceed
        if ($count >= $limit) {
            if ($throw) {
                throw new Exception(
                    sprintf(__('Limit of %s backups for module %s exceed'), $this->backup_limit, $id)
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
        $o = 0;
        $parts = explode("\n", $content);
        foreach($parts as $li => $part) {
            $m = explode($func . '(', $part);
            for($i = 1; $i < count($m); $i++) {
                $lines[$o] = $li+1;
                $o++;
            }
        }
        // split content by translation function
        $parts = explode($func . '(', $content);
        // remove fisrt element from array
        array_shift($parts);
        // put back first parenthesis
        $parts = array_map(function($v){ return '(' . $v;}, $parts);
        // walk through parts
        $p = 0;
        foreach($parts as $part) {
            // find pairs of parenthesis
            preg_match_all("/\((?:[^\)\(]+|(?R))*+\)/s", $part, $subparts);
            // find quoted strings (single or double)
            preg_match_all("/\'(?:[^\']+)\'|\"(?:[^\"]+)\"/s", $subparts[0][0], $strings);
            // strings exist
            if (!empty($strings[0])) {
                // remove quotes
                $strings[0] = array_map(function($v){ return substr($v, 1, -1);}, $strings[0]);
                // filter duplicate strings (only check first string for plurals form)
                if (!in_array($strings[0][0], $duplicate)) {
                    // fill final array
                    $final_strings[] = [$strings[0], $lines[$p]];
                    $duplicate[] = $strings[0][0];
                }
            }
            $p++;
        }
        return $final_strings;
    }

    /**
     * Extract messages from a tpl contents
     *
     * support plurals
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
        $o = 0;
        $parts = explode("\n", $content);
        foreach($parts as $li => $part) {
            $m = explode('{{' . $func . ' ', $part);
            for($i = 1; $i < count($m); $i++) {
                $lines[$o] = $li+1;
                $o++;
            }
        }
        // split content by translation function
        if (!preg_match_all('/\{\{' . preg_quote($func) . '\s([^}]+)\}\}/', $content, $parts)) {
            return $final_strings;
        }
        // walk through parts
        $p = 0;
        foreach($parts[1] as $part) {
            // strings exist
            if (!empty($part)) {
                // filter duplicate strings
                if (!in_array($part, $duplicate)) {
                    // fill final array
                    $final_strings[] = [[$part], $lines[$p]];
                    $duplicate[] = $part;
                }
            }
            $p++;
        }
        return $final_strings;
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
            $smap = array('"', "\n", "\t", "\r");
            $rmap = array('\\"', '\\n"' . "\n" . '"', '\\t', '\\r');
            return trim((string) str_replace($smap, $rmap, $string));
        } else {
            $smap = array('/"\s+"/', '/\\\\n/', '/\\\\r/', '/\\\\t/', '/\\\"/');
            $rmap = array('', "\n", "\r", "\t", '"');
            return trim((string) preg_replace($smap, $rmap, $string));
        }
    }
    //@}
}