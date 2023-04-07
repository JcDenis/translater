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
use dcNamespace;
use dcNsProcess;
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            self::growUp();

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    /**
     * Upgrade plugin
     *
     * @return bool Upgrade done
     */
    private static function growUp()
    {
        $current = dcCore::app()->getVersion(My::id());

        // use short settings id
        if ($current && version_compare($current, '2022.12.22', '<')) {
            $record = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'translater' "
            );
            while ($record->fetch()) {
                if (preg_match('/^translater_(.*?)$/', $record->f('setting_id'), $match)) {
                    $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                    $cur->setField('setting_id', $match[1]);
                    $cur->setField('setting_ns', My::id());
                    $cur->update(
                        "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'translater' " .
                        'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . dcCore::app()->con->escapeStr((string) $record->f('blog_id')) . "' "))
                    );
                }
            }

            return true;
        }

        return false;
    }
}
