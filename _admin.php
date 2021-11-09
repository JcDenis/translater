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

$core->addBehavior('adminModulesListGetActions', ['translaterAdminBehaviors', 'adminModulesGetActions']);
$core->addBehavior('adminModulesListDoActions', ['translaterAdminBehaviors', 'adminModulesDoActions']);
$core->addBehavior('adminDashboardFavorites', ['translaterAdminBehaviors', 'adminDashboardFavorites']);

$_menu['Plugins']->addItem(
    __('Translater'),
    $core->adminurl->get('translater'),
    dcPage::getPF('translater/icon.png'),
    preg_match(
        '/' . preg_quote($core->adminurl->get('translater')) . '(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->isSuperAdmin()
);

class translaterAdminBehaviors
{
    /** @var dcTranslater dcTranslater instance */
    private static $translater = null;

    /**
     * Create instance of dcTranslater once
     *
     * @param  dcCore $core     dcCore instance
     *
     * @return dcTranslater     dcTranslater instance
     */
    private static function translater(dcCore $core): dcTranslater
    {
        if (!is_a(self::$translater, 'dcTranslater')) {
            self::$translater = new dcTranslater($core, false);
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
            || !self::translater($list->core)->{$prop['type'] . '_menu'}
            || !$list->core->auth->isSuperAdmin()
        ) {
            return null;
        }
        if (self::translater($list->core)->hide_default
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

        $list->core->adminurl->redirect(
            'translater',
            ['part' => 'module', 'type' => $type, 'module' => key($_POST['translater'])],
            '#module-lang'
        );
    }

    /**
     * Add dashboard favorites icon
     *
     * @param  dcCore       $core   dcCore instance
     * @param  dcFavorites  $favs   dcFavorites instance
     */
    public static function adminDashboardFavorites(dcCore $core, dcFavorites$favs): void
    {
        $favs->register('translater', [
            'title'       => __('Translater'),
            'url'         => $core->adminurl->get('translater'),
            'small-icon'  => urldecode(dcPage::getPF('translater/icon.png')),
            'large-icon'  => urldecode(dcPage::getPF('translater/icon-big.png')),
            'permissions' => $core->auth->isSuperAdmin()
        ]);
    }
}
