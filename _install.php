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
    if (!dcCore::app()->newVersion(
        basename(__DIR__),
        dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
    )) {
        return null;
    }
    $translater = new dcTranslater(false);
    if (!$translater->growUp()) {
        $translater->writeSettings(false);
    }

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
