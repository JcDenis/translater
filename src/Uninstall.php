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

class Uninstall
{
    protected static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process($uninstaller): ?bool
    {
        if (!self::$init) {
            return false;
        }

        $uninstaller->addUserAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            My::id(),
            /* description */
            __('delete all settings')
        );

        $uninstaller->addUserAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            My::id(),
            /* description */
            __('delete plugin files')
        );

        $uninstaller->addUserAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            My::id(),
            /* description */
            __('delete the version number')
        );

        $uninstaller->addDirectAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            My::id(),
            /* description */
            sprintf(__('delete all %s settings'), My::id())
        );

        $uninstaller->addDirectAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            My::id(),
            /* description */
            sprintf(__('delete %s plugin files'), My::id())
        );

        $uninstaller->addDirectAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            My::id(),
            /* description */
            sprintf(__('delete %s version number'), My::id())
        );

        return true;
    }
}
