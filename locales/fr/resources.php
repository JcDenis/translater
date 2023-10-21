<?php
/**
 * @file
 * @brief       The plugin translater locales resources
 * @ingroup     translater
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
foreach (['index', 'type', 'module', 'lang', 'config'] as $v) {
    \Dotclear\App::backend()->resources()->set('help', 'translater.' . $v, __DIR__ . '/help/translater.' . $v . '.html');
}
