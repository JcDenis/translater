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

use adminModulesList;
use dcCore;
use Dotclear\Helper\Html\Form\Submit;
use html;

class BackendBehaviors
{
    /** @var Translater Translater instance */
    private static $translater = null;

    /**
     * Create instance of Translater once
     *
     * @return Translater     Translater instance
     */
    private static function translater(): Translater
    {
        if (!is_a(self::$translater, Translater::class)) {
            self::$translater = new Translater(false);
        }

        return self::$translater;
    }

    /**
     * Add button to go to module translation
     *
     * @param  adminModulesList     $list   adminModulesList instance
     * @param  string               $id     Module id
     * @param  array                $prop   Module properties
     *
     * @return string                       HTML submit button
     */
    public static function adminModulesGetActions(adminModulesList $list, string $id, array $prop): ?string
    {
        if ($list->getList() != $prop['type'] . '-activate'
            || !self::translater()->getSetting($prop['type'] . '_menu')
            || !dcCore::app()->auth->isSuperAdmin()
        ) {
            return null;
        }
        if (self::translater()->hide_default
            && in_array($id, My::defaultDistribModules($prop['type']))
        ) {
            return null;
        }

        return (new Submit(['translater[' . html::escapeHTML($id) . ']', null]))->value(__('Translate'))->render();
    }

    /**
     * Redirect to module translation
     *
     * @param  adminModulesList     $list       adminModulesList instance
     * @param  array                $modules    Selected modules ids
     * @param  string               $type       List type (plugin|theme)
     */
    public static function adminModulesDoActions(adminModulesList $list, array $modules, string $type): void
    {
        if (empty($_POST['translater']) || !is_array($_POST['translater'])) {
            return;
        }

        dcCore::app()->adminurl->redirect(
            My::id(),
            ['part' => 'module', 'type' => $type, 'module' => key($_POST['translater'])],
            '#module-lang'
        );
    }
}
