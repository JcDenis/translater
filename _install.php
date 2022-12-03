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

try {
    $id = basename(__DIR__);

    if (version_compare(dcCore::app()->getVersion($id), dcCore::app()->plugins->moduleInfo($id, 'version'), '>=')) {
        return null;
    }
    $translater = new dcTranslater(false);
    $translater->writeSettings(false);
    dcCore::app()->setVersion($id, dcCore::app()->plugins->moduleInfo($id, 'version'));

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
