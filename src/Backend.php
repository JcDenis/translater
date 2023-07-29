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
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Favorites;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        dcCore::app()->addBehaviors([
            'adminModulesListGetActions' => [BackendBehaviors::class, 'adminModulesGetActions'],
            'adminModulesListDoActions'  => [BackendBehaviors::class, 'adminModulesDoActions'],
            'adminDashboardFavoritesV2'  => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                    //'permissions' => null,
                ]);
            },
        ]);

        return true;
    }
}
