<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\L10n;
use Dotclear\Module\ModuleDefine;

/**
 * @brief       translater lang tools class.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class TranslaterLang
{
    /**
     * Lang name.
     *
     * @var     string  $name
     */
    public readonly string $name;

    /**
     * Lang plural forms.
     *
     * @var     array<int, string>  $plural
     */
    public readonly array $plural;

    /**
     * Constructor.
     *
     * @param   TranslaterModule    $module     The module
     * @param   string              $code       The lang code
     */
    public function __construct(
        private TranslaterModule $module,
        public readonly string $code
    ) {
        $this->name   = L10n::getLanguageName($code);
        $this->plural = explode(':', L10n::getLanguagePluralExpression($code));
    }

    /**
     * Get a lang messages.
     *
     * @return  array<string, array<string, mixed>>   The messages ids and translations
     */
    public function getMessages(): array
    {
        $res       = [];
        $m_msgids  = $this->getMsgIds();
        $m_msgstrs = $this->getMsgStrs();

        foreach ($this->module->translater->getModules() as $module) {
            if ($module->id != $this->module->id) {
                $m_o_msgstrs[$module->id] = $this->module->translater->getlang($module, $this->code)->getMsgStrs();
            }
        }

        # Add Dotclear str
        $dc_define               = (new ModuleDefine('dotclear'))->set('root', App::config()->dotclearRoot());
        $dc_module               = new TranslaterModule($this->module->translater, $dc_define);
        $dc_lang                 = new TranslaterLang($dc_module, $this->code);
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
            if (!is_string($rs['msgid']) || !isset($res[$rs['msgid']])) {
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
                if (!is_string($rs['msgid']) || !isset($res[$rs['msgid']])) {
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
     * Get messages ids.
     *
     * @return array<int, array<string, mixed>>     The messages ids
     */
    public function getMsgIds(): array
    {
        $res      = [];
        $scan_ext = ['php'];
        if ($this->module->translater->scan_tpl) {
            $scan_ext[] = 'html';
        }

        $files = Translater::scandir($this->module->root);
        foreach ($files as $file) {
            $extension = Files::getExtension($file);
            if (is_dir($this->module->root . DIRECTORY_SEPARATOR . $file) || !in_array($extension, $scan_ext)) {
                continue;
            }
            $contents = file_get_contents($this->module->root . '/' . $file);
            $msgs     = [];
            if ($extension == 'php') {
                # php files
                $msgs = Translater::extractPhpMsgs((string) $contents);
            } elseif ($extension == 'html') {
                # tpl files
                $msgs = Translater::extractTplMsgs((string) $contents);
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
     * Get messages translations.
     *
     * @return  array<int, array<string, string|array<int, string>>>   The messages translations
     */
    public function getMsgStrs(): array
    {
        $res = $exists = $scanned = [];

        $langs = $this->module->getLangsPath();
        if (!isset($langs[$this->code])) {
            return $res;
        }

        foreach ($langs[$this->code] as $file) {
            if (in_array($file, $scanned)) {
                continue;
            }
            $scanned[] = $file;
            $path      = Path::clean($this->module->locales . DIRECTORY_SEPARATOR . $file);

            if (Translater::isPoFile($file)) {
                $po = L10n::parsePoFile($path);
                if (!is_array($po)) {
                    continue;
                }
                $entries = $po[1];
                foreach ($entries as $entry) {
                    $res[] = [
                        'msgid'        => $entry['msgid'],
                        'msgid_plural' => $entry['msgid_plural'] ?? '',
                        'msgstr'       => is_array($entry['msgstr']) ? $entry['msgstr'] : [$entry['msgstr']],
                        'lang'         => $this->code,
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
