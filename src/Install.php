<?php

declare(strict_types=1);

namespace Dotclear\Plugin\translater;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Statement\SelectStatement;
use Exception;

/**
 * @brief       translater install class.
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            self::growUp();

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
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
        $current = App::version()->getVersion(My::id());

        // use short settings id
        if ($current && version_compare($current, '2022.12.22', '<')) {
            $sql    = new SelectStatement();
            $record = $sql
                ->column('*')
                ->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                ->where("setting_ns = 'translater' ")
                ->select();

            if (!$record) {
                return true;
            }

            while ($record->fetch()) {
                if (preg_match('/^translater_(.*?)$/', $record->f('setting_id'), $match)) {
                    $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
                    $cur->setField('setting_id', $match[1]);
                    $cur->setField('setting_ns', My::id());
                    $cur->update(
                        "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'translater' " .
                        'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . App::con()->escapeStr((string) $record->f('blog_id')) . "' "))
                    );
                }
            }

            return true;
        }

        return false;
    }
}
