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
class dcTranslaterModule
{
    /** @var dCore dcCore instance */
    public $core = null;
    /** @var dcTranslater dcTranslater instance */
    public $translater = null;

    /** @var array Module properies */
    private $prop = [];

    /** @var string Backup file regexp */
    private $backup_file_regexp = '/^l10n-%s-(.*?)-[0-9]*?\.bck\.zip$/';

    /** @var string Locales file regexp */
    private $locales_file_regexp = '/^(.*?)\/locales\/(.*?)\/(.*?)(.po|.lang.php)$/';

    public function __construct(dcTranslater $translater, array $module)
    {
        $this->core       = $translater->core;
        $this->translater = $translater;
        $this->prop       = $module;

        $this->prop['root'] = path::real($this->prop['root']);
        $i = path::info($this->prop['root']);
        $this->prop['basename'] = $i['basename'];
        $this->prop['locales'] = $this->prop['root'] . '/locales';
    }

    /**
     * Get a module property
     * 
     * @param  string $key The module property key
     * @return mixed       The module property value or null
     */
    public function get(string $key)
    {  
        return array_key_exists($key, $this->prop) ? $this->prop[$key] : null;
    }

    /**
     * Magic get
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /// @name backup methods
    //@{
    /**
     * Find backup folder of a module
     * 
     * @param  boolean $throw Silently failed
     * @return mixed          The backup folder directory or false
     */
    public function getBackupRoot(bool $throw = false)
    {
        $dir = false;
        switch($this->translater->backup_folder) {
            case 'module':
                if ($this->prop['root_writable']) {
                    $dir = $this->prop['locales'];
                }
            break;

            case 'plugin':
                $tmp = path::real(array_pop(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT)));
                if ($tmp && is_writable($tmp)) {
                    $dir = $tmp;
                }
            break;

            case 'public':
                $tmp = path::real($this->core->blog->public_path);
                if ($tmp && is_writable($tmp)) {
                    $dir = $tmp;
                }
            break;

            case 'cache':
                $tmp = path::real(DC_TPL_CACHE);
                if ($tmp && is_writable($tmp)) {
                    @mkDir($tmp . '/l10n');
                    $dir = $tmp . '/l10n';
                }
            break;

            case 'translater':
            $tmp = path::real($this->core->plugins->moduleRoot('translater'));
            if ($tmp && is_writable($tmp)) {
                @mkDir($tmp . '/locales');
                $dir = $tmp . '/locales';
            }
            break;
        }
        if (!$dir && $throw) {
            throw new Exception(sprintf(
                __('Failed to find backups folder for module %s'), $id
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

        $res = [];
        $files = dcTranslater::scandir($backup);
        foreach($files AS $file) {
            $is_backup = preg_match(sprintf($this->backup_file_regexp, preg_quote($this->prop['id'])), $file, $m);

            if (is_dir($backup . '/' . $file) 
                || !$is_backup 
                || !l10n::isCode($m[1])
            ) {
                continue;
            }

            if ($return_filename) {
                $res[] = $file;
            } else {
                $res[$m[1]][$file]['code']   = $m[1];
                $res[$m[1]][$file]['name']   = l10n::getLanguageName($m[1]);
                $res[$m[1]][$file]['path']   = path::info($backup . '/' . $file);
                $res[$m[1]][$file]['time']   = filemtime($backup . '/' . $file);
                $res[$m[1]][$file]['size']   = filesize($backup . '/' . $file);
                $res[$m[1]][$file]['module'] = $this->prop['id'];
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

        if (!is_dir($this->prop['locales'] . '/' . $lang)) {
            throw new Exception(sprintf(
                __('Failed to find language %s'), $lang
            ));
        }

        $res = [];
        $files = dcTranslater::scandir($this->prop['locales'] . '/' . $lang);
        foreach($files as $file) {
            if (!is_dir($this->prop['locales'] . '/' . $lang . '/' . $file) 
                && (dcTranslater::isLangphpFile($file) || dcTranslater::isPoFile($file))
            ) {
                $res[$this->prop['locales'] . '/' . $lang . '/' .$file] = 
                    $this->prop['id'] . '/locales/' . $lang . '/' . $file;
            }
        }

        if (!empty($res)) {
            dcTranslater::isBackupLimit($backup, $this->translater->backup_limit, true);

            @set_time_limit(300);
            $fp = fopen($backup . '/l10n-' . $this->prop['id'] . '-' . $lang . '-' . time() . '.bck.zip', 'wb');
            $zip = new fileZip($fp);
            foreach($res AS $from => $to) {
                $zip->addFile($from, $to);
            }
            $zip->write();
            $zip->close();
            unset($zip);

            return true;
        }
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
                __('Failed to find file %s'), $file
            ));
        }

        $zip = new fileUnzip($backup . '/' . $file);
        $zip_files = $zip->getFilesList();

        foreach($zip_files AS $zip_file) {
            $f = $this->parseZipFilename($zip_file, true);
            $zip->unzip($zip_file, $this->prop['locales'] . '/' . $f['lang'] . '/' . $f['group'] . $f['ext']);
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

        $is_backup = preg_match(sprintf($this->backup_file_regexp, preg_quote($this->prop['id'])), $file, $m);

        if (!file_exists($backup . '/' . $file) 
            || !$is_backup 
            || !l10n::isCode($m[1])
        ) {
            return false;
        }

        if (!files::isDeletable($backup . '/' . $file)) {
            throw new Exception(sprintf(
                __('Failed to delete file %s'), $file
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
        files::uploadStatus($zip_file);

        $imported = false;
        $not_overwrited = [];
        $res = [];

        # Load Unzip object
        $zip = new fileUnzip($zip_file['tmp_name']);
        $files = $zip->getFilesList();

        foreach($files as $file) {
            $f = $this->parseZipFilename($file, true);

            if (!$this->translater->import_overwrite 
                && file_exists($this->prop['locales'] . '/' . $f['lang'] . '/' . $f['group'] . $f['ext'])
            ) {
                $not_overwrited[] = implode('-', [$f['lang'], $f['group'], $f['ext']]);
                continue;
            }

            $res[] = [
                'from' => $file, 
                'root' => $this->prop['locales'] . '/' . $f['lang'], 
                'to'   => $this->prop['locales'] . '/' . $f['lang'] . '/' . $f['group'] . $f['ext']
            ];
        }

        foreach ($res as $rs) {
            if (!is_dir($rs['root'])) {
                files::makeDir($rs['root'], true);
            }

            $zip->unzip($rs['from'], $rs['to']);
            $imported = true;
        }
        $zip->close();
        unlink($zip_file['tmp_name']);

        if (!empty($not_overwrited)) {
            throw new Exception(sprintf(
                __('Some languages has not been overwrited %s'), implode(', ', $not_overwrited)
            ));
        } elseif (!$done) {
            throw new Exception(sprintf(
                __('Nothing to import from %s'), $zip_file['name']
            ));
        }
        return true;
    }

    /**
     * Export (to output) language pack
     * 
     * @param  array $langs     Langs to export
     */
    public function exportPack(array $langs)
    {
        if (empty($langs)) {
            throw new Exception(
                __('Nothing to export')
            );
        }

        $filename = files::tidyFileName($this->translater->export_filename);
        if (empty($filename)) {
            throw new Exception(
                __('Export mask is not set in plugin configuration')
            );
        }

        $res = [];
        foreach($langs AS $lang) {
            if (!is_dir($this->prop['locales'] . '/' . $lang)) {
                continue;
            }

            $files = dcTranslater::scandir($this->prop['locales'] . '/' . $lang);
            foreach($files as $file) {

                if (is_dir($this->prop['locales'] . '/' . $lang . '/' . $file) 
                    || !dcTranslater::isLangphpFile($file) 
                    && !dcTranslater::isPoFile($file)
                ) {
                    continue;
                }

                $res[$this->prop['locales'] . '/' . $lang . '/' . $file] = 
                    $this->prop['id'] . '/locales/' . $lang . '/' . $file;
            }
        }

        if (empty($res)) {
            throw new Exception(
                __('Nothing to export')
            );
        }

        @set_time_limit(300);
        $fp = fopen('php://output', 'wb');
        $zip = new fileZip($fp);
        foreach($res as $from => $to) {
            $zip->addFile($from, $to);
        }

        $filename = files::tidyFileName(dt::str(str_replace(
            ['timestamp', 'module', 'type', 'version'],
            [time(), $this->prop['id'], $this->prop['type'], $this->prop['version']],
            $this->translater->export_filename
        )));

        header('Content-Disposition: attachment;filename=' . $filename . '.zip');
        header('Content-Type: application/x-zip');
        $zip->write();
        unset($zip);
        exit;
    }

    /**
     * Parse zip filename to module, lang info
     * 
     * @param  string  $file  The zip filename
     * @param  boolean $throw Silently failed
     * @return mixed          Array of file info
     */
    public function parseZipFilename(string $file = '', bool $throw = false): array
    {
        $is_file = preg_match('/^(.*?)\/locales\/(.*?)\/(.*?)(.po|.lang.php)$/', $file, $f);

        if ($is_file) {
            $module = $f[1] == $this->prop['id'] ?$f[1] : false;
            $lang = l10n::isCode($f[2]) ? $f[2] : false;
            $group = in_array($f[3], dctranslater::$allowed_l10n_groups) ? $f[3] : false;
            $ext = dctranslater::isLangphpFile($f[4]) || dctranslater::isPoFile($f[4]) ? $f[4] : false;
        }

        if (!$is_file || !$module || !$lang || !$group || !$ext) {
            if ($throw) {
                throw new Exception(sprintf(
                    __('Zip file %s is not in translater format'), $file
                ));
            }
            return [];
        }
        return [
            'module' => $module,
            'lang'   => $lang,
            'group'  => $group,
            'ext'    => $ext
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

        $prefix = preg_match('/(locales(.*))$/', $this->prop['locales']) ? 'locales' : '';

        $files = dcTranslater::scandir($this->prop['locales']);
        foreach($files as $file) {
            if (!preg_match('/.*?locales\/([^\/]*?)\/([^\/]*?)(.lang.php|.po)$/', $prefix . $file, $m)) {
                continue;
            }

            if (!l10n::isCode($m[1])) {
                continue;
            }

            if ($return_path) {
                $res[$m[1]][] = $file; // Path
            } else {
                $res[$m[1]] = l10n::getLanguageName($m[1]); // Lang name
            }
        }
        return $res;
    }

    /**
     * List of used langs of a module
     * 
     * @return array The list of iso names and codes
     */
    public function getUsedLangs()
    {
        return array_flip($this->getLangs());
    }

    /**
     * List of unsused langs of a module
     * 
     * @return array The list of iso names and codes
     */
    public function getUnusedLangs()
    {
        return array_diff(l10n::getISOcodes(true, false), $this->getUsedLangs());
    }

    /**
     * Add a lang to a module
     * 
     * @param string $lang      The lang id
     * @param string $from_lang The lang to copy from
     * @return boolean          True on success
     */
    public function addLang(string $lang, string $from_lang = '')
    {
        if (!l10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'), $lang
            ));
        }

        $langs = $this->getLangs();
        if (isset($langs[$lang])) {
            throw new Exception(sprintf(
                __('Language %s already exists'), $lang
            ));
        }

        files::makeDir($this->prop['locales'] . '/' . $lang, true);

        if (!empty($from_lang) && !isset($langs[$from_lang])) {
            throw new Exception(sprintf(
                __('Failed to copy file from language %s'), $from_lang
            ));
        }

        if (!empty($from_lang) && isset($langs[$from_lang])) {
            $files = dcTranslater::scandir($this->prop['locales'] . '/' . $from_lang);
            foreach($files as $file) {
                if (is_dir($this->prop['locales'] . '/' . $from_lang . '/' . $file) 
                    || !dcTranslater::isLangphpFile($file) 
                    && !dcTranslater::isPoFile($file)
                ) {
                    continue;
                }

                files::putContent($this->prop['locales'] . '/' . $lang . '/' . $file,
                    file_get_contents($this->prop['locales'] . '/' . $from_lang . '/' . $file)
                );
            }
        } else {
            $this->setPoContent($lang, 'main', []);
            $this->setLangphpContent($lang, 'main', []);
        }
    }

    /**
     * Update an existing lang
     * 
     * @param  string $lang   The lang
     * @param  array $msgs    The messages
     */
    public function updLang(string $lang, array $msgs)
    {
        if (!l10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'), $lang
            ));
        }

        $langs = $this->getLangs();
        if (!isset($langs[$lang])) {
            throw new Exception(sprintf(
                __('Failed to find language %s'), $lang
            ));
        }

        if ($this->translater->backup_auto) {
            $this->createBackup($lang);
        }

        $rs = [];
        foreach($msgs as $msg) {
            if (empty($msg['msgstr'][0])) {
                continue;
            }
            $rs[$msg['group']][] = $msg;
        }

        foreach(dcTranslater::$allowed_l10n_groups as $group) {
            if (isset($rs[$group])) {
                continue;
            }

            $po_file = $this->prop['locales'] . '/' . $lang . '/' . $group . '.po';
            $langphp_file = $this->prop['locales'] . '/' . $lang . '/' . $group . '.lang.php';

            if (file_exists($po_file)) {
                unlink($po_file);
            }
            if (file_exists($langphp_file)) {
                unlink($langphp_file);
            }
        }

        if (empty($rs)) {
            throw new Exception(sprintf(
                __('No string to write, language %s deleted'), $lang
            ));
        }

        foreach($rs as $group => $msgs) {
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
        if (!l10n::isCode($lang)) {
            throw new Exception(sprintf(
                __('Unknow language %s'), $lang
            ));
        }

        $files = $this->getLangs(true);
        if (!isset($files[$lang])) {
            throw new Exception(sprintf(
                __('Failed to find language %s'), $lang
            ));
        }

        foreach($files[$lang] as $file) {
            unlink($this->prop['locales'] . '/' . $file);
        }

        $dir = dcTranslater::scandir($this->prop['locales'] . '/' . $lang);
        if (empty($dir)) {
            rmdir($this->prop['locales'] . '/' . $lang);
        }

        $loc = dcTranslater::scandir($this->prop['locales']);
        if (empty($loc)) {
            rmdir($this->prop['locales']);
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
    private function setPoContent(string $lang, string $group, array $msgs)
    {
        $lang = new dcTranslaterLang($this, $lang);

        $content = '';
        if ($this->translater->parse_comment) {
            $content .= 
            '# Language: ' . $lang->name . "\n" .
            '# Module: ' . $this->id . " - " . $this->version . "\n" .
            '# Date: ' . dt::str('%Y-%m-%d %H:%M:%S') . "\n";

            if ($this->translater->parse_user && !empty($this->translater->parse_userinfo)) {
                $search = dctranslater::$allowed_user_informations;
                foreach($search AS $n) {
                    $replace[] = $this->core->auth->getInfo('user_' . $n);
                }
                $info = trim(str_replace($search, $replace, $this->translater->parse_userinfo));
                if (!empty($info)) {
                    $content .= '# Author: ' . html::escapeHTML($info) . "\n";
                }
            }
            $content .= 
            '# Translated with translater ' . $this->core->plugins->moduleInfo('translater', 'version') . "\n";
        }
        $content .= 
        "\n".
        "msgid \"\"\n" .
        "msgstr \"\"\n" .
        '"Content-Type: text/plain; charset=UTF-8\n"' . "\n" .
        '"Project-Id-Version: ' . $this->id . ' ' . $this->version . '\n"' . "\n" .
        '"POT-Creation-Date: \n"' . "\n" .
        '"PO-Revision-Date: ' . date('c') . '\n"' . "\n" .
        '"Last-Translator: ' . $this->core->auth->getInfo('user_cn') . '\n"' . "\n" .
        '"Language-Team: \n"' . "\n" .
        '"MIME-Version: 1.0\n"' . "\n" .
        '"Content-Transfer-Encoding: 8bit\n"' . "\n" .
        '"Plural-Forms: nplurals=2; plural=(n > 1);\n"' . "\n\n";

        $comments = [];
        if ($this->translater->parse_comment) {
            $msgids = $lang->getMsgids();
            foreach($msgids as $msg) {
                $comments[$msg['msgid']] = (isset($comments[$msg['msgid']]) ?
                    $comments[$msg['msgid']] : '') .
                    '#: '.trim($msg['file'],'/') . ':' . $msg['line'] . "\n";
            }
        }

        foreach($msgs as $msg) {
            if (empty($msg['msgstr'][0])) {
                continue;
            }
            if ($this->translater->parse_comment && isset($comments[$msg['msgid']])) {
                $content .= $comments[$msg['msgid']];
            }
            $content .= 'msgid "' . dcTranslater::poString($msg['msgid'], true) . '"' . "\n";
            if (empty($msg['msgid_plural'])) {
                $content .= 'msgstr "' . dcTranslater::poString($msg['msgstr'][0], true) . '"' . "\n";
            } else {
                $content .= 'msgid_plural "' . dcTranslater::poString($msg['msgid_plural'], true) . '"' . "\n";
                foreach($msg['msgstr'] as $i => $plural) {
                    $content .= 'msgstr[' . $i . '] "' . dcTranslater::poString(($msg['msgstr'][$i] ?: ''), true) . '"' . "\n";
                }
            }
            $content .= "\n";
        }

        $file = $this->locales . '/' . $lang->code . '/' . $group . '.po';
        $path = path::info($file);
        if (is_dir($path['dirname']) && !is_writable($path['dirname']) 
         || file_exists($file) && !is_writable($file)) {
            throw new Exception(sprintf(
                __('Failed to grant write acces on file %s'), $file
            ));
        }

        if (!($f = @files::putContent($file, $content))) {
            throw new Exception(sprintf(
                __('Failed to write file %s'), $file
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
    private function setLangphpContent(string $lang, string $group, array $msgs)
    {
        if (!$this->translater->write_langphp) {
            return null;
        }

        $lang = new dcTranslaterLang($this, $lang);

        $content = '';
        if ($this->translater->parse_comment) {
            $content .= 
            '// Language: ' . $lang->name . " \n" .
            '// Module: ' . $this->id . " - " . $this->verison . "\n" .
            '// Date: ' . dt::str('%Y-%m-%d %H:%M:%S') . " \n";

            if ($this->translater->parse_user && !empty($this->translater->parse_userinfo)) {
                $search = dcTranslater::$allowed_user_informations;
                foreach($search as $n) {
                    $replace[] = $this->core->auth->getInfo('user_' . $n);
                }               
                $info = trim(str_replace($search, $replace,$this->translater->parse_userinfo));
                if (!empty($info)) {
                    $content .= '// Author: ' . html::escapeHTML($info) . "\n";
                }
            }
            $content .= 
            '// Translated with dcTranslater - ' . $this->core->plugins->moduleInfo('translater', 'version') . " \n\n";
        }

        l10n::generatePhpFileFromPo($this->locales . '/' . $lang->code . '/' . $group, $content);
    }
    //@}
}