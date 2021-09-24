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
    if (version_compare($core->getVersion($id), $this->moduleInfo($id, 'version'), '>=')) {
        return null;
    }

    $t = new dcTranslater($core);
    $s = $t->getDefaultSettings();
    foreach($s as $v) {
        $t->setSetting($v[0], $v[1], false);
    }

    $core->setVersion($id, $this->moduleInfo($id, 'version'));

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
return false;