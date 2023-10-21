<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;

/**
 * @brief       translater backend behaviors class.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    /**
     * Translater instance.
     *
     * @var     ?Translater     $translater
     */
    private static $translater = null;

    /**
     * Create instance of Translater once.
     *
     * @return  Translater  Translater instance
     */
    private static function translater(): Translater
    {
        if (is_null(self::$translater)) {
            self::$translater = new Translater(false);
        }

        return self::$translater;
    }

    /**
     * Add button to go to module translation.
     *
     * @param   ModulesList             $list   ModulesList instance
     * @param   string                  $id     Module id
     * @param   array<string, mixed>    $prop   Module properties
     *
     * @return  ?string     HTML submit button
     */
    public static function adminModulesGetActions(ModulesList $list, string $id, array $prop): ?string
    {
        if (!is_string($prop['type'])) {
            return null;
        }
        if ($list->getList() != $prop['type'] . '-activate'
            || !self::translater()->getSetting($prop['type'] . '_menu')
            || !App::auth()->isSuperAdmin()
        ) {
            return null;
        }
        if (self::translater()->hide_default
            && in_array($id, My::defaultDistribModules($prop['type']))
        ) {
            return null;
        }

        return (new Submit(['translater[' . Html::escapeHTML($id) . ']']))->value(__('Translate'))->render();
    }

    /**
     * Redirect to module translation.
     *
     * @param   ModulesList         $list       ModulesList instance
     * @param   array<int, string>  $modules    Selected modules ids
     * @param   string              $type       List type (plugin|theme)
     */
    public static function adminModulesDoActions(ModulesList $list, array $modules, string $type): void
    {
        if (empty($_POST['translater']) || !is_array($_POST['translater'])) {
            return;
        }

        My::redirect(['part' => 'module', 'type' => $type, 'module' => key($_POST['translater'])], '#module-lang');
    }
}
