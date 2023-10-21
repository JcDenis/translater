<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Exception;

/**
 * @brief       translater vars helper.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ManageVars
{
    /**
     * self instance.
     *
     * @var     ManageVars  $instance
     */
    private static $instance;

    /**
     * translater instance.
     *
     * @var     Translater  $translater
     */
    public readonly Translater $translater;

    /**
     * Module instance.
     *
     * @var     TranslaterModule    $module
     */
    public readonly ?TranslaterModule $module;

    /**
     * Lang instance.
     *
     * @var     TranslaterLang  $lang
     */
    public readonly ?TranslaterLang $lang;

    /**
     * The module type.
     *
     * @var     string  $type
     */
    public readonly string $type;

    /**
     * The action.
     *
     * @var     string  $action
     */
    public readonly string $action;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->translater = new Translater();

        $type   = $_REQUEST['type']   ?? $this->translater->start_page ?: '';
        $module = $_REQUEST['module'] ?? null;
        $lang   = $_REQUEST['lang']   ?? null;
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
                App::error()->add($e->getMessage());
                $module = null;
            }
        }
        //check if module lang exists
        if (!empty($module) && !empty($lang)) {
            try {
                $lang = $this->translater->getLang($module, $lang);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
                $lang = null;
            }
        }

        $this->type   = $type;
        $this->module = $module;
        $this->lang   = $lang;
        $this->action = $action;
    }

    /**
     * Get self instance.
     *
     * @return  ManageVars  self instance
     */
    public static function init(): ManageVars
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
