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
    return null;
}
$id = 'translater';

try {
    if (version_compare($core->getVersion($id), $core->plugins->moduleInfo($id, 'version'), '>=')) {
        return null;
    }
    $translater = new dcTranslater($core, false);
    $translater->writeSettings(false);
    $core->setVersion($id, $core->plugins->moduleInfo($id, 'version'));

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

return false;
