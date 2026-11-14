# translater

Notes for AI assistants working on the Dotclear plugin translater.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Translate your Dotclear plugins and themes.

Admin page and configuration page. Nothing on the public side.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/Install.php`: install and update, default settings.
* `src/Manage.php`: admin page of the module.
* `src/ManageVars.php`: admin page (vars).
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Settings.php`: module settings.
* `src/Translater.php`: main tools class.
* `src/TranslaterLang.php`: lang tools class.
* `src/TranslaterModule.php`: module tools class.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `js/`: `backend.js`.
* Behaviors used: `adminModulesListGetActions`, `adminModulesListDoActions`, `adminDashboardFavoritesV2`.

## Pitfalls

* None recorded yet: add here the traps met while working on the module.

## Testing

* Check the admin page, the configuration page, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
