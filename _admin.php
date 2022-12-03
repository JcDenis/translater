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

if (!dcCore::app()->auth->isSuperAdmin()) {
    return null;
}

dcCore::app()->addBehavior('adminModulesListGetActions', ['translaterAdminBehaviors', 'adminModulesGetActions']);
dcCore::app()->addBehavior('adminModulesListDoActions', ['translaterAdminBehaviors', 'adminModulesDoActions']);
dcCore::app()->addBehavior('adminDashboardFavoritesV2', ['translaterAdminBehaviors', 'adminDashboardFavoritesV2']);

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Translater'),
    dcCore::app()->adminurl->get('translater'),
    dcPage::getPF('translater/icon.svg'),
    preg_match(
        '/' . preg_quote(dcCore::app()->adminurl->get('translater')) . '(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    dcCore::app()->auth->isSuperAdmin()
);

class translaterAdminBehaviors
{
    /** @var dcTranslater dcTranslater instance */
    private static $translater = null;

    /**
     * Create instance of dcTranslater once
     *
     * @return dcTranslater     dcTranslater instance
     */
    private static function translater(): dcTranslater
    {
        if (!is_a(self::$translater, 'dcTranslater')) {
            self::$translater = new dcTranslater(false);
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
            || !self::translater()->{$prop['type'] . '_menu'}
            || !dcCore::app()->auth->isSuperAdmin()
        ) {
            return null;
        }
        if (self::translater()->hide_default
            && in_array($id, dcTranslater::$default_distrib_modules[$prop['type']])
        ) {
            return null;
        }

        return
            ' <input type="submit" name="translater[' .
            html::escapeHTML($id) .
            ']" value="' . __('Translate') . '" /> ';
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
            'translater',
            ['part' => 'module', 'type' => $type, 'module' => key($_POST['translater'])],
            '#module-lang'
        );
    }

    /**
     * Add dashboard favorites icon
     *
     * @param  dcFavorites  $favs   dcFavorites instance
     */
    public static function adminDashboardFavoritesV2(dcFavorites $favs): void
    {
        $favs->register('translater', [
            'title'       => __('Translater'),
            'url'         => dcCore::app()->adminurl->get('translater'),
            'small-icon'  => urldecode(dcPage::getPF('translater/icon.svg')),
            'large-icon'  => urldecode(dcPage::getPF('translater/icon.svg')),
            //'permissions' => null,
        ]);
    }
}
