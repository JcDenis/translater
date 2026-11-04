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
    private static ManageVars $instance;

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

        $type    = is_string($_REQUEST['type'] ?? null) ? $_REQUEST['type'] : $this->translater->start_page;
        $module  = is_string($_REQUEST['module'] ?? null) ? $_REQUEST['module'] : '';
        $lang    = is_string($_REQUEST['lang'] ?? null) ? $_REQUEST['lang'] : '';
        $action  = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $tmodule = $tlang = null;

        // check module type
        if (!in_array($type, ['plugin', 'theme'])) {
            $type = '';
        }
        // check if module exists
        if ($type !== '' && $module !== '') {
            try {
                $tmodule = $this->translater->getModule($type, $module);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }
        //check if module lang exists
        if ($tmodule instanceof TranslaterModule && $lang !== '') {
            try {
                $tlang = $this->translater->getLang($tmodule, $lang);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        $this->type   = $type;
        $this->module = $tmodule;
        $this->lang   = $tlang;
        $this->action = $action;
    }

    /**
     * Get self instance.
     *
     * @return  ManageVars  self instance
     */
    public static function init(): ManageVars
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
