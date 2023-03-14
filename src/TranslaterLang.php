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

use files;
use l10n;
use path;

class TranslaterLang
{
    /** @var Translater Translater instance */
    public $translater = null;
    /** @var TranslaterModule TranslaterModule instance */
    public $module = null;

    /** @var array Lang properies */
    private $prop = [];

    public function __construct(TranslaterModule $module, string $lang)
    {
        $this->translater = $module->translater;
        $this->module     = $module;

        $this->prop['code']   = $lang;
        $this->prop['name']   = l10n::getLanguageName($lang);
        $this->prop['plural'] = explode(':', l10n::getLanguagePluralExpression($lang));
    }

    /**
     * Get a lang property
     *
     * @param  string $key The lang property key
     * @return mixed       The lang property value or null
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

    /**
     * Get a lang messages
     *
     * @return array The messages ids and translations
     */
    public function getMessages(): array
    {
        $res       = [];
        $m_msgids  = $this->getMsgIds();
        $m_msgstrs = $this->getMsgStrs();

        foreach ($this->translater->getModules() as $module) {
            if ($module->id != $this->module->get('id')) {
                $m_o_msgstrs[$module->get('id')] = $this->translater->getlang($module, $this->get('code'))->getMsgStrs();
            }
        }
        $dc_module               = new TranslaterModule($this->translater, ['id' => 'dotclear', 'root' => DC_ROOT]);
        $dc_lang                 = new TranslaterLang($dc_module, $this->get('code'));
        $m_o_msgstrs['dotclear'] = $dc_lang->getMsgStrs();

        # From id list
        foreach ($m_msgids as $rs) {
            $res[$rs['msgid']]['files'][]   = [trim($rs['file'], '/'), $rs['line']];
            $res[$rs['msgid']]['group']     = 'main';
            $res[$rs['msgid']]['plural']    = $rs['msgid_plural'];
            $res[$rs['msgid']]['msgstr']    = [''];
            $res[$rs['msgid']]['in_dc']     = false;
            $res[$rs['msgid']]['o_msgstrs'] = [];
        }

        # From str list
        foreach ($m_msgstrs as $rs) {
            if (!isset($res[$rs['msgid']])) {
                $res[$rs['msgid']]['files'][]   = [];
                $res[$rs['msgid']]['in_dc']     = false;
                $res[$rs['msgid']]['o_msgstrs'] = [];
            }
            $res[$rs['msgid']]['group']  = $rs['group'];
            $res[$rs['msgid']]['plural'] = $rs['msgid_plural'];
            $res[$rs['msgid']]['msgstr'] = is_array($rs['msgstr']) ? $rs['msgstr'] : [$rs['msgstr']];
            $res[$rs['msgid']]['in_dc']  = false;
        }

        # From others str list
        foreach ($m_o_msgstrs as $o_module => $o_msgstrs) {
            foreach ($o_msgstrs as $rs) {
                if (!isset($res[$rs['msgid']])) {
                    continue;
                }

                $res[$rs['msgid']]['o_msgstrs'][] = [
                    'msgstr' => is_array($rs['msgstr']) ? $rs['msgstr'] : [$rs['msgstr']],
                    'module' => $o_module,
                    'file'   => $rs['file'],
                ];
                if ($o_module == 'dotclear') {
                    $res[$rs['msgid']]['in_dc'] = true;
                }
            }
        }

        return $res;
    }

    /**
     * Get messages ids
     *
     * @return array The messages ids
     */
    public function getMsgIds(): array
    {
        $res      = [];
        $scan_ext = ['php'];
        if ($this->translater->get('scan_tpl')) {
            $scan_ext[] = 'html';
        }

        $files = Translater::scandir($this->module->get('root'));
        foreach ($files as $file) {
            $extension = files::getExtension($file);
            if (is_dir($this->module->get('root') . '/' . $file) || !in_array($extension, $scan_ext)) {
                continue;
            }
            $contents = file_get_contents($this->module->get('root') . '/' . $file);
            $msgs     = [];
            # php files
            if ($extension == 'php') {
                $msgs = Translater::extractPhpMsgs($contents);

            # tpl files
            } elseif ($extension == 'html') {
                $msgs = Translater::extractTplMsgs($contents);
            }
            foreach ($msgs as $msg) {
                $res[] = [
                    'msgid'        => Translater::encodeMsg($msg[0][0]),
                    'msgid_plural' => empty($msg[0][1]) ? '' : Translater::encodeMsg($msg[0][1]),
                    'file'         => $file,
                    'line'         => $msg[1],
                ];
            }

            unset($contents);
        }

        return $res;
    }

    /**
     * Get messages translations
     *
     * @return array The messages translations
     */
    public function getMsgStrs(): array
    {
        $res = $exists = $scanned = [];

        $langs = $this->module->getLangs(true);
        if (!isset($langs[$this->get('code')])) {
            return $res;
        }

        foreach ($langs[$this->get('code')] as $file) {
            if (in_array($file, $scanned)) {
                continue;
            }
            $scanned[] = $file;
            $path      = path::clean($this->module->get('locales') . '/' . $file);

            if (Translater::isPoFile($file)) {
                $po = l10n::parsePoFile($path);
                if (!is_array($po)) {
                    continue;
                }
                $entries = $po[1];
                foreach ($entries as $entry) {
                    $res[] = [
                        'msgid'        => $entry['msgid'],
                        'msgid_plural' => $entry['msgid_plural'] ?? '',
                        'msgstr'       => is_array($entry['msgstr']) ? $entry['msgstr'] : [$entry['msgstr']],
                        'lang'         => $this->get('code'),
                        'type'         => 'po',
                        'path'         => $path,
                        'file'         => basename($file),
                        'group'        => str_replace('.po', '', basename($file)),
                    ];
                    $exists[] = $entry['msgid'];
                }
            }
        }

        return $res;
    }
}
