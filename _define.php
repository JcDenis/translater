<?php
/**
 * @file
 * @brief       The plugin translater definition
 * @ingroup     translater
 *
 * @defgroup    translater Plugin translater.
 *
 * Translate your Dotclear plugins and themes.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Translater',
    'Translate your Dotclear plugins and themes',
    'Jean-Christian Denis & contributors',
    '2025.03.03',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-03T14:17:24+00:00',
    ]
);
