<?php
/**
 * @file
 * @brief       The plugin translater locales resources
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
foreach (['index' => 'help', 'type' => 'type', 'module' => 'module', 'lang' => 'lang', 'config' => 'config'] as $page => $file) {
    \Dotclear\App::backend()->resources()->set('help', 'translater-' . $page, __DIR__ . '/help/' . $file . '.html');
}
