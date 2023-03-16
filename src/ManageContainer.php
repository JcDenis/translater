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

class ManageContainer
{
    private static ManageContainer $container;

    public readonly Translater $translater;
    public readonly string|TranslaterModule $module;
    public readonly string|TranslaterLang $lang;
    public readonly string $type;
    public readonly string $action;

    protected function __construct()
    {
        $this->translater = new Translater();

        $type   = $_REQUEST['type']   ?? $this->translater->start_page ?: '';
        $module = $_REQUEST['module'] ?? '';
        $lang   = $_REQUEST['lang']   ?? '';
        $action = $_POST['action']    ?? '';

        // check module type
        if (!in_array($type, ['plugin', 'theme'])) {
            $type = '';
        }
        // check if module exists
        if (!empty($type) && !empty($module)) {
            try {
                $module = $this->translater->getModule($type, $module);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
                $module = '';
            }
        }
        //check if module lang exists
        if (!empty($module) && !empty($lang)) {
            try {
                $lang = $this->translater->getLang($module, $lang);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
                $lang = '';
            }
        }

        $this->type   = $type;
        $this->module = $module;
        $this->lang   = $lang;
        $this->action = $action;
    }

    public static function init(): ManageContainer
    {
        if (!(self::$container instanceof self)) {
            self::$container = new self();
        }

        return self::$container;
    }
}
