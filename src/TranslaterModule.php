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
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Exception;

/**
 * Translater tools.
 */
class TranslaterModule
{
    /** @var string Module id */
    public readonly string $id;

    /** @var string Module type */
    public readonly string $type;

    /** @var string Module name */
    public readonly string $name;

    /** @var string Module author */
    public readonly string $author;

    /** @var string Module version */
    public readonly string $version;

    /** @var bool Module root writable */
    public readonly bool $root_writable;

    /** @var string Module root (cleaned) */
    public readonly string $root;

    /** @var string Module locales root path */
    public readonly string $locales;

    /** @var Translater Translater instance */
    public readonly Translater $translater;

    /** @var string Backup file regexp */
    private $backup_file_regexp = '/^l10n-%s-(.*?)-[0-9]*?\.bck\.zip$/';

    public function __construct(Translater $translater, dcModuleDefine $define)
    {
        $this->translater    = $translater;
        $this->id            = $define->get('id');
        $this->type          = $define->get('type');
        $this->name          = $define->get('name');
        $this->author        = $define->get('author');
        $this->version       = $define->get('version');
        $this->root_writable = $define->get('root_writable');
        $this->root          = (string) Path::real($define->get('root'), false);
        $this->locales       = $this->root . DIRECTORY_SEPARATOR . My::LOCALES_FOLDER;
    }

    /// @name backup methods
    //@{
    /**
     * Find backup folder of a module
     *
     * @param  boolean $throw Silently failed
     * @return string|false   The backup folder directory or false
     */
    public function getBackupRoot(bool $throw = false): string|false
    {
        $dir = false;
        switch ($this->translater->backup_folder) {
            case 'module':
                if ($this->root_writable) {
                    $dir = $this->locales;
                }

                break;

            case 'plugin':
                $exp = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
                $tmp = Path::real(array_pop($exp));
                if ($tmp !== false && is_writable($tmp)) {
                    $dir = $tmp;
                }

                break;

            case 'public':
                $tmp = Path::real((string) dcCore::app()->blog?->public_path);
                if ($tmp !== false && is_writable($tmp)) {
                    $dir = $tmp;
                }

                break;

            case 'cache':
                $tmp = Path::real(DC_TPL_CACHE);
                if ($tmp !== false && is_writable($tmp)) {
                    @mkDir($tmp . '/l10n');
                    $dir = $tmp . '/l10n';
                }

                break;

            case 'translater':
                $tmp = Path::real(dcCore::app()->plugins->moduleRoot(My::id()));
                if ($tmp !== false && is_writable($tmp)) {
                    @mkDir($tmp . DIRECTORY_SEPARATOR . My::LOCALES_FOLDER);
                    $dir = $tmp . DIRECTORY_SEPARATOR . My::LOCALES_FOLDER;
                }

                break;
        }
        if (!$dir && $throw) {
            throw new Exception(sprintf(
                __('Failed to find backups folder for module %s'),
                $this->id
            ));
        }

        return $dir;
    }

    /**
     * Get a list of available backups
     *
     * @param  boolean $return_filename Return only filenames
     * @return array                    The module backups info
     */
    public function getBackups(bool $return_filename = false): array
    {
        $backup = $this->getBackupRoot();
        if (!$backup) {
            return [];
        }

        $res   = [];
        $files = Translater::scandir($backup);
        foreach ($files as $file) {
            $is_backup = preg_match(sprintf($this->backup_file_regexp, preg_quote($this->id)), $file, $m);

            if (is_dir($backup . '/' . $file)
                || !$is_backup
                || !L10n::isCode($m[1])
            ) {
                continue;
            }

            if ($return_filename) {
                $res[] = $file;
            } else {
                $res[$m[1]][$file]['code']   = $m[1];
                $res[$m[1]][$file]['name']   = L10n::getLanguageName($m[1]);
                $res[$m[1]][$file]['path']   = Path::info($backup . '/' . $file);
                $res[$m[1]][$file]['time']   = filemtime($backup . '/' . $file);
                $res[$m[1]][$file]['size']   = filesize($backup . '/' . $file);
                $res[$m[1]][$file]['module'] = $this->id;
            }
        }

        return $res;
    }

    /**
     * Create a backup
     *
     * @param  string $lang The backup lang
     * @return boolean      True on success
     */
    public function createBackup(string $lang): bool
    {
        $backup = $this->getBackupRoot(true);
        if (!$backup) {
            return false;
        }

        $dir = $this->locales . DIRECTORY_SEPARATOR . $lang;
        if (!is_dir($dir)) {
            throw new Exception(sprintf(
                __('Failed to find language %s'),
                $lang
            ));
        }

        $res   = [];
        $files = Translater::scandir($dir);
        foreach ($files as $file) {
            if (!is_dir($dir . DIRECTORY_SEPARATOR . $file)
                && (Translater::isLangphpFile($file) || Translater::isPoFile($file))
            ) {
                $res[$dir . DIRECTORY_SEPARATOR . $file] = implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $file]);
            }
        }

        if (!empty($res)) {
            Translater::isBackupLimit($this->id, $backup, $this->translater->backup_limit, true);

            @set_time_limit(300);
            $zip = new Zip($backup . '/l10n-' . $this->id . '-' . $lang . '-' . time() . '.bck.zip');
            foreach ($res as $from => $to) {
                $zip->addFile($from, $to);
            }
            $zip->close();
            unset($zip);

            return true;
        }

        return false;
    }

    /**
     * Retore a backup
     *
     * @param  string $file   The backup filename
     * @return boolean        True on success
     */
    public function restoreBackup(string $file): bool
    {
        $backup = self::getBackupRoot(true);

        if (!file_exists($backup . '/' . $file)) {
            throw new Exception(sprintf(
                __('Failed to find file %s'),
                $file
            ));
        }

        $zip       = new Unzip($backup . '/' . $file);
        $zip_files = $zip->getFilesList();

        foreach ($zip_files as $zip_file) {
            $f = $this->parseZipFilename($zip_file, true);
            $zip->unzip($zip_file, implode(DIRECTORY_SEPARATOR, [$this->locales, $f['lang'], $f['group'] . $f['ext']]));
            $done = true;
        }
        $zip->close();
        unset($zip);

        return true;
    }

    /**
     * Delete a module backup
     *
     * @param  string $file The backup filename
     * @return boolean       True on success
     */
    public function deleteBackup(string $file): bool
    {
        $backup = $this->getBackupRoot(true);

        $is_backup = preg_match(sprintf($this->backup_file_regexp, preg_quote($this->id)), $file, $m);

        if (!file_exists($backup . '/' . $file)
            || !$is_backup
            || !L10n::isCode($m[1])
        ) {
            return false;
        }

        if (!Files::isDeletable($backup . '/' . $file)) {
            throw new Exception(sprintf(
                __('Failed to delete file %s'),
                $file
            ));
        }

        unlink($backup . '/' . $file);

        return true;
    }

    /**
     * Import a language pack
     *
     * @param  array $zip_file The uploaded file info
     * @return boolean         True on success
     */
    public function importPack(array $zip_file): bool
    {
        Files::uploadStatus($zip_file);

        $imported       = false;
        $not_overwrited = [];
        $res            = [];

        # Load Unzip object
        $zip   = new Unzip($zip_file['tmp_name']);
        $files = $zip->getFilesList();

        foreach ($files as $file) {
            $f = $this->parseZipFilename($file, true);

            if (!$this->translater->import_overwrite
                && file_exists(implode(DIRECTORY_SEPARATOR, [$this->locales, $f['lang'], $f['group'] . $f['ext']]))
            ) {
                $not_overwrited[] = implode('-', [$f['lang'], $f['group'], $f['ext']]);

                continue;
            }

            $res[] = [
                'from' => $file,
                'root' => implode(DIRECTORY_SEPARATOR, [$this->locales, $f['lang']]),
                'to'   => implode(DIRECTORY_SEPARATOR, [$this->locales, $f['lang'], $f['group'] . $f['ext']]),
            ];
        }

        foreach ($res as $rs) {
            if (!is_dir($rs['root'])) {
                Files::makeDir($rs['root'], true);
            }

            $zip->unzip($rs['from'], $rs['to']);
            $imported = true;
        }
        $zip->close();
        unlink($zip_file['tmp_name']);

        if (!empty($not_overwrited)) {
            throw new Exception(sprintf(
                __('Some languages has not been overwrited %s'),
                implode(', ', $not_overwrited)
            ));
        } elseif (!$imported) {
            throw new Exception(sprintf(
                __('Nothing to import from %s'),
                $zip_file['name']
            ));
        }

        return true;
    }

    /**
     * Export (to output) language pack
     *
     * @param  array $langs     Langs to export
     */
    public function exportPack(array $langs): void
    {
        if (empty($langs)) {
            throw new Exception(
                __('Nothing to export')
            );
        }

        $filename = Files::tidyFileName($this->translater->export_filename);
        if (empty($filename)) {
            throw new Exception(
                __('Export mask is not set in plugin configuration')
            );
        }

        $res = [];
        foreach ($langs as $lang) {
            if (!is_dir($this->locales . DIRECTORY_SEPARATOR . $lang)) {
                continue;
            }

            $files = Translater::scandir($this->locales . DIRECTORY_SEPARATOR . $lang);
            foreach ($files as $file) {
                if (is_dir(implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $file]))
                    || !Translater::isLangphpFile($file)
                    && !Translater::isPoFile($file)
                ) {
                    continue;
                }

                $res[implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $file])] = implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $file]);
            }
        }

        if (empty($res)) {
            throw new Exception(
                __('Nothing to export')
            );
        }

        @set_time_limit(300);
        $zip = new Zip('php://output');
        foreach ($res as $from => $to) {
            $zip->addFile($from, $to);
        }

        $filename = Files::tidyFileName(Date::str(str_replace(
            ['timestamp', 'module', 'type', 'version'],
            [time(), $this->id, $this->type, $this->version],
            $this->translater->export_filename
        )));

        header('Content-Disposition: attachment;filename=' . $filename . '.zip');
        header('Content-Type: application/x-zip');
        $zip->close();
        unset($zip);
        exit;
    }

    /**
     * Parse zip filename to module, lang info
     *
     * @param  string  $file  The zip filename
     * @param  boolean $throw Silently failed
     * @return array          Array of file info
     */
    public function parseZipFilename(string $file = '', bool $throw = false): array
    {
        $is_file = preg_match('/^(.*?)\/' . preg_quote(My::LOCALES_FOLDER) . '\/(.*?)\/(.*?)(.po|.lang.php)$/', $file, $f);

        if ($is_file) {
            $module = $f[1] == $this->id ? $f[1] : false;
            $lang   = L10n::isCode($f[2]) ? $f[2] : false;
            $group  = in_array($f[3], My::l10nGroupsCombo()) ? $f[3] : false;
            $ext    = Translater::isLangphpFile($f[4]) || Translater::isPoFile($f[4]) ? $f[4] : false;
        }

        if (!$is_file || !$module || !$lang || !$group || !$ext) {
            if ($throw) {
                throw new Exception(sprintf(
                    __('Zip file %s is not in translater format'),
                    $file
                ));
            }

            return [];
        }

        return [
            'module' => $module,
            'lang'   => $lang,
            'group'  => $group,
            'ext'    => $ext,
        ];
    }
    //@}

    /// @name lang methods
    //@{
    /**
     * List available langs of a module
     *
     * @param  boolean $return_path Return path or name
     * @return array                The lang list
     */
    public function getLangs(bool $return_path = false): array
    {
        $res = [];

        $prefix = preg_match('/(' . preg_quote(My::LOCALES_FOLDER) . '(.*))$/', $this->locales) ? My::LOCALES_FOLDER : '';

        $files = Translater::scandir($this->locales);
        foreach ($files as $file) {
            if (!preg_match('/.*?' . preg_quote(My::LOCALES_FOLDER) . '\/([^\/]*?)\/([^\/]*?)(.lang.php|.po)$/', $prefix . $file, $m)) {
                continue;
            }

            if (!L10n::isCode($m[1])) {
                continue;
            }

            if ($return_path) {
                $res[$m[1]][] = $file; // Path
            } else {
                $res[$m[1]] = L10n::getLanguageName($m[1]); // Lang name
            }
        }

        return $res;
    }

    /**
     * List of used langs of a module
     *
     * @return array The list of iso names and codes
     */
    public function getUsedLangs(): array
    {
        return array_flip($this->getLangs());
    }

    /**
     * List of unsused langs of a module
     *
     * @return array The list of iso names and codes
     */
    public function getUnusedLangs(): array
    {
        return array_diff(L10n::getISOcodes(true, false), $this->getUsedLangs());
    }

    /**
     * Add a lang to a module
     *
     * @param string $lang      The lang id
     * @param string $from_lang The lang to copy from
     * @return boolean          True on success
     */
    public function addLang(string $lang, string $from_lang = ''): bool
    {
        if (!L10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'),
                $lang
            ));
        }

        $langs = $this->getLangs();
        if (isset($langs[$lang])) {
            throw new Exception(sprintf(
                __('Language %s already exists'),
                $lang
            ));
        }

        Files::makeDir($this->locales . DIRECTORY_SEPARATOR . $lang, true);

        if (!empty($from_lang) && !isset($langs[$from_lang])) {
            throw new Exception(sprintf(
                __('Failed to copy file from language %s'),
                $from_lang
            ));
        }

        if (!empty($from_lang) && isset($langs[$from_lang])) {
            $files = Translater::scandir($this->locales . DIRECTORY_SEPARATOR . $from_lang);
            foreach ($files as $file) {
                if (is_dir(implode(DIRECTORY_SEPARATOR, [$this->locales, $from_lang, $file]))
                    || !Translater::isLangphpFile($file)
                    && !Translater::isPoFile($file)
                ) {
                    continue;
                }

                Files::putContent(
                    implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $file]),
                    (string) file_get_contents(implode(DIRECTORY_SEPARATOR, [$this->locales, $from_lang, $file]))
                );
            }
        } else {
            $this->setPoContent($lang, 'main', []);
            $this->setLangphpContent($lang, 'main', []);
        }

        return true;
    }

    /**
     * Update an existing lang
     *
     * @param  string $lang   The lang
     * @param  array $msgs    The messages
     */
    public function updLang(string $lang, array $msgs): void
    {
        if (!L10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'),
                $lang
            ));
        }

        $langs = $this->getLangs();
        if (!isset($langs[$lang])) {
            throw new Exception(sprintf(
                __('Failed to find language %s'),
                $lang
            ));
        }

        if ($this->translater->backup_auto) {
            $this->createBackup($lang);
        }

        $rs = [];
        foreach ($msgs as $msg) {
            if (empty($msg['msgstr'][0])) {
                continue;
            }
            $rs[$msg['group']][] = $msg;
        }

        foreach (My::l10nGroupsCombo() as $group) {
            if (isset($rs[$group])) {
                continue;
            }

            $po_file      = implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $group . '.po']);
            $langphp_file = implode(DIRECTORY_SEPARATOR, [$this->locales, $lang, $group . '.lang.php']);

            if (file_exists($po_file)) {
                unlink($po_file);
            }
            if (file_exists($langphp_file)) {
                unlink($langphp_file);
            }
        }

        if (empty($rs)) {
            throw new Exception(sprintf(
                __('No string to write, language %s deleted'),
                $lang
            ));
        }

        foreach ($rs as $group => $msgs) {
            $group = (string) $group;
            $this->setPoContent($lang, $group, $msgs);
            $this->setLangphpContent($lang, $group, $msgs);
        }
    }

    /**
     * Delete a lang
     *
     * @param  string  $lang          The lang code
     * @param  boolean $del_empty_dir Also remove empty locales dir
     * @return boolean                True on success
     */
    public function delLang(string $lang, bool $del_empty_dir = true): bool
    {
        # Path is right formed
        if (!L10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'),
                $lang
            ));
        }

        $files = $this->getLangs(true);
        if (!isset($files[$lang])) {
            throw new Exception(sprintf(
                __('Failed to find language %s'),
                $lang
            ));
        }

        foreach ($files[$lang] as $file) {
            unlink($this->locales . DIRECTORY_SEPARATOR . $file);
        }

        $dir = Translater::scandir($this->locales . DIRECTORY_SEPARATOR . $lang);
        if (empty($dir)) {
            rmdir($this->locales . DIRECTORY_SEPARATOR . $lang);
        }

        $loc = Translater::scandir($this->locales);
        if (empty($loc)) {
            rmdir($this->locales);
        }

        return true;
    }

    /**
     * Construct and parse a .po file
     *
     * @param string $lang   The lang code
     * @param string $group  The lang group
     * @param array $msgs    The strings
     */
    private function setPoContent(string $lang, string $group, array $msgs): void
    {
        $lang = new TranslaterLang($this, $lang);

        $content = '';
        if ($this->translater->parse_comment) {
            $content .= '# Language: ' . $lang->name . "\n" .
            '# Module: ' . $this->id . ' - ' . $this->version . "\n" .
            '# Date: ' . Date::str('%Y-%m-%d %H:%M:%S') . "\n";

            if ($this->translater->parse_user && $this->translater->parse_userinfo != '') {
                $search  = My::defaultUserInformations();
                $replace = [];
                foreach ($search as $n) {
                    $replace[] = dcCore::app()->auth?->getInfo('user_' . $n);
                }
                $info = trim(str_replace($search, $replace, $this->translater->parse_userinfo));
                if (!empty($info)) {
                    $content .= '# Author: ' . Html::escapeHTML($info) . "\n";
                }
            }
            $content .= '# Translated with translater ' . dcCore::app()->plugins->moduleInfo(My::id(), 'version') . "\n\n";
        }
        $content .= "msgid \"\"\n" .
        "msgstr \"\"\n" .
        '"Content-Type: text/plain; charset=UTF-8\n"' . "\n" .
        '"Project-Id-Version: ' . $this->id . ' ' . $this->version . '\n"' . "\n" .
        '"POT-Creation-Date: \n"' . "\n" .
        '"PO-Revision-Date: ' . date('c') . '\n"' . "\n" .
        '"Last-Translator: ' . dcCore::app()->auth?->getInfo('user_cn') . '\n"' . "\n" .
        '"Language-Team: \n"' . "\n" .
        '"MIME-Version: 1.0\n"' . "\n" .
        '"Content-Transfer-Encoding: 8bit\n"' . "\n" .
        '"Plural-Forms: nplurals=2; plural=(n > 1);\n"' . "\n\n";

        $comments = [];
        if ($this->translater->parse_comment) {
            $msgids = $lang->getMsgids();
            foreach ($msgids as $msg) {
                $comments[$msg['msgid']] = ($comments[$msg['msgid']] ?? '') .
                    '#: ' . trim($msg['file'], '/') . ':' . $msg['line'] . "\n";
            }
        }

        foreach ($msgs as $msg) {
            if (empty($msg['msgstr'][0])) {
                continue;
            }
            if ($this->translater->parse_comment && isset($comments[$msg['msgid']])) {
                $content .= $comments[$msg['msgid']];
            }
            $content .= 'msgid "' . Translater::poString($msg['msgid'], true) . '"' . "\n";
            if (empty($msg['msgid_plural'])) {
                $content .= 'msgstr "' . Translater::poString($msg['msgstr'][0], true) . '"' . "\n";
            } else {
                $content .= 'msgid_plural "' . Translater::poString($msg['msgid_plural'], true) . '"' . "\n";
                foreach ($msg['msgstr'] as $i => $plural) {
                    $content .= 'msgstr[' . $i . '] "' . Translater::poString(($msg['msgstr'][$i] ?: ''), true) . '"' . "\n";
                }
            }
            $content .= "\n";
        }

        $file = implode(DIRECTORY_SEPARATOR, [$this->locales, $lang->code, $group . '.po']);
        $path = Path::info($file);
        if (is_dir($path['dirname']) && !is_writable($path['dirname'])
         || file_exists($file)       && !is_writable($file)) {
            throw new Exception(sprintf(
                __('Failed to grant write acces on file %s'),
                $file
            ));
        }

        if (!($f = @Files::putContent($file, $content))) {
            throw new Exception(sprintf(
                __('Failed to write file %s'),
                $file
            ));
        }
    }

    /**
     * Construct and write a .lang.php file
     *
     * @param string $lang   The lang code
     * @param string $group  The lang group
     * @param array $msgs    The strings
     */
    private function setLangphpContent(string $lang, string $group, array $msgs): void
    {
        if (!$this->translater->write_langphp) {
            return;
        }

        $lang = new TranslaterLang($this, $lang);

        $content = '';
        if ($this->translater->parse_comment) {
            $content .= '// Language: ' . $lang->name . "\n" .
            '// Module: ' . $this->id . ' - ' . $this->version . "\n" .
            '// Date: ' . Date::str('%Y-%m-%d %H:%M:%S') . "\n";

            if ($this->translater->parse_user && !empty($this->translater->parse_userinfo)) {
                $search  = My::defaultUserInformations();
                $replace = [];
                foreach ($search as $n) {
                    $replace[] = dcCore::app()->auth?->getInfo('user_' . $n);
                }
                $info = trim(str_replace($search, $replace, $this->translater->parse_userinfo));
                if (!empty($info)) {
                    $content .= '// Author: ' . Html::escapeHTML($info) . "\n";
                }
            }
            $content .= '// Translated with Translater - ' . dcCore::app()->plugins->moduleInfo(My::id(), 'version') . "\n\n";
        }

        L10n::generatePhpFileFromPo(implode(DIRECTORY_SEPARATOR, [$this->locales, $lang->code, $group]), $content);
    }
    //@}
}
