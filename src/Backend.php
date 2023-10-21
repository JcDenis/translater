<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Favorites;

/**
 * @brief       translater backend class.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
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

        App::behavior()->addBehaviors([
            'adminModulesListGetActions' => BackendBehaviors::adminModulesGetActions(...),
            'adminModulesListDoActions'  => BackendBehaviors::adminModulesDoActions(...),
            'adminDashboardFavoritesV2'  => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                ]);
            },
        ]);

        return true;
    }
}
