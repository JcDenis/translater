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

use dcAdmin;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = dcCore::app()->auth->isSuperAdmin();
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminModulesListGetActions' => [BackendBehaviors::class, 'adminModulesGetActions'],
            'adminModulesListDoActions'  => [BackendBehaviors::class, 'adminModulesDoActions'],
            'adminDashboardFavoritesV2'  => function (dcFavorites $favs): void {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => dcCore::app()->adminurl->get(My::id()),
                    'small-icon' => urldecode(dcPage::getPF(My::id() . '/icon.svg')),
                    'large-icon' => urldecode(dcPage::getPF(My::id() . '/icon.svg')),
                    //'permissions' => null,
                ]);
            },
        ]);

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl->get(My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match(
                '/' . preg_quote(dcCore::app()->adminurl->get(My::id())) . '(&.*)?$/',
                $_SERVER['REQUEST_URI']
            ),
            dcCore::app()->auth->isSuperAdmin()
        );

        return true;
    }
}