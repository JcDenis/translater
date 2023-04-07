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
use dcModuleDefine;
use dcThemes;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Text;
use Exception;
use l10n;

/**
 * Translater tools.
 */
class Translater extends Settings
{
    /** @var array $modules List of modules we could work on */
    private $modules = [];

    /**
     * translater instance
     *
     * @param boolean $full Also load modules
     */
    public function __construct(bool $full = true)
    {
        parent::__construct();

        if ($full) {
            $this->loadModules();
        }
    }

    /// @name modules methods
    //@{
    /**
     * Load array of modules infos by type of modules
     */
    private function loadModules(): void
    {
        $this->modules = ['theme' => [], 'plugin' => []];

        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules((string) dcCore::app()->blog?->themes_path, null);
        }

        foreach ([
            dcCore::app()->themes->getDefines(['state' => dcModuleDefine::STATE_ENABLED]),
            dcCore::app()->plugins->getDefines(['state' => dcModuleDefine::STATE_ENABLED]),
        ] as $modules) {
            foreach ($modules as $define) {
                $this->modules[$define->get('type')][$define->get('id')] = new TranslaterModule($this, $define);
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
     * @return TranslaterModule   The TranslaterModule instance
     */
    public function getModule(string $type, string $id): TranslaterModule
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
     * @param  TranslaterModule   $module     TranslaterModule instance
     * @param  string               $lang       The lang iso code
     *
     * @return TranslaterLang                 TranslaterLang instance or false
     */
    public function getLang(TranslaterModule $module, string $lang): TranslaterLang
    {
        if (!l10n::isCode($lang)) {
            throw new Exception(
                sprintf(__('Failed find language %s'), $lang)
            );
        }

        return new TranslaterLang($module, $lang);
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
        $path = (string) Path::real($path, false);
        if (empty($path) || !is_dir($path) || !is_readable($path)) {
            return [];
        }

        $files = Files::scandir($path);
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
        return Text::toUTF8(stripslashes(trim($str)));
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
        return Files::getExtension($file) == 'po';
    }

    /**
     * Try if a file is a .lang.php file
     *
     * @param  string  $file The path to test
     * @return boolean       Success
     */
    public static function isLangphpFile(string $file): bool
    {
        return Files::getExtension($file) == 'php' && stristr($file, '.lang.php');
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
