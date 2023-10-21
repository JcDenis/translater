translater 2023.10.21
===========================================================
* Require Dotclear 2.28
* Require PHP 8.1
* Upgrade to Dotclear 2.28
* Code review (phpstan)

translater 2023.08.15
===========================================================
* Require Dotclear 2.27
* Require PHP 8.1
* Move third party repository
* Use Dotclear style for CHANGELOG

translater 2023.07.29
===========================================================
* require Dotclear 2.27
* require PHP 8.1+
* update to Dotclear 2.27-dev

translater 2023.06.18
===========================================================
* require dotclear 2.26
* require php 8.1+
* fix settings (again)

translater 2023.05.13
===========================================================
* require dotclear 2.26
* require php 8.1+
* use define php_min
* fix settings

translater 2023.04.23
===========================================================
* require dotclear 2.26
* require php 8.1+
* use latest dotclear namespace
* fix nullsafe warnings

translater 2023.03.19
===========================================================
* fix init check
* fix some phpstan warnings

translater 2023.03.17
===========================================================
* use container for Manage vars
* use modules define
* use readonly properties for modules, langs, settings, manage page
* fix init check
* fix previously introduced bug

translater 2023.03.16
===========================================================
* require php 8.1
* use container for settings
* fix previously introduced bug

translater 2023.03.15
===========================================================
* update to Dotclear 2.26-dev
* use namespace

translater 2022.12.26
===========================================================
* fix wrong dt::str parameter (thx Pierre Van Glabeke)
* fix backup limit (unlimited)
* groups admin behaviors
* remove settings addNamespace method
* use abstract plugin id and settings ns

translater 2022.12.22
===========================================================
* use SVG icon
* use short settings id
* fix install
* fix autoload
* fix permissions

translater 2022.11.20
===========================================================
* fix compatibility with Dotclear 2.24 (required)

translater 2021.11.06
===========================================================
* sort modules by id
* fix extra whitespace in exported files
* update to PSR12

translater 2021.09.28
===========================================================
* Fix help (thx Pierre Van Glabeke)
* Fix translations (thx Pierre Van Glabeke)
* Fix wrong number line for source files
* Fix false positive on unquoted srtings
* Fix empty line on .po file

translater 2021.09.25
===========================================================
* add support for plural
* add dashboard icon
* fix constante for official modules
* fix superadmin permissions
* fix global settings
* light interface
* remove modules list tab and add button to existing lists
* remove multi-modules import/export
* .po export become a requirment
* use l10n functions to generate .lang.php files

translater 2021.09.02
===========================================================
* clean up code and fix typo

translater 2021.08.18
===========================================================
* Fixed PSR-2 Coding Style
* Move settings to config file

translater 2018.10.26 - Pierre Van Glabeke
===========================================================
* Bug avec php 7.2 (https://forum.dotclear.org/viewtopic.php?pid=342810#p342810)

translater 2018.10.18 - Pierre Van Glabeke
===========================================================
* Modifs localisation

translater 2018.02.14 - Pierre Van Glabeke
===========================================================
* Suppression ?> en fin de lang.php

translater 2017.05.10 - Pierre Van Glabeke
===========================================================
*  Suppression ligne 614 de "continue" dans \inc\class.dc.translater.php

translater 2016.08.20 - Pierre Van Glabeke
===========================================================
*  Ajout Pluriel dans po
*  Ajout favori

translater 2016.07.08 - Pierre Van Glabeke
===========================================================
*  Modifs localisation

translater 2013.05.11
===========================================================
*  Rewrited proposal tools
*  Added Microsoft translation tool
*  Updated Google translation tool
*  Removed permissions, now required superadmin
*  Fixed page title and messages and contents
*  Moved all sub-pages into one page

translater 1.5 - 2010.09.01
===========================================================
*  Added option to set defaut tab (closes #552)
*  Fixed occurrences count (closes #551)
*  Fixed regxep (closes #550)
*  Cleaned design (thanks to osku)

translater 1.4.2 - 2010.09.01
===========================================================
*  Tried to fix crash with regexp on parsing .po file

translater 1.4.1 - 2010.06.26
===========================================================
*  Fixed crash on .po files
*  Fixed toggle function
*  Fixed admin crash on non 2.2
*  Fixed minor bugs
*  Added option to hide default modules of Dotclear

translater 1.4 - 2010.06.05
===========================================================
*  Switched to DC 2.2
*  Added toogle list of existing translation
*  Fixed google translate (now uses Google ajax API)
*  Removed "simple mode"
*  Changed admin interface (easy, light, fast)

translater 1.3 - 2009.10.25
===========================================================
*  Added babelfish help
*  Added behaviors on files writing
*  Fixed regexp again
*  Changed priority to .po files instead of .lang.php files

translater 1.2 - 2009.10.10
===========================================================
*  Added direct text copy and paste
*  Added grouping file change
*  Fixed some typo

translater 1.1 - 2009.10.02
===========================================================
*  Added the proposed translation 
*  Rewrited settings system
*  Speed up expert mode

translater 1.0 - 2009.09.28
===========================================================
*  Added translation of template files. closes #250

translater 0.9 - 2009.09.23
===========================================================
*  Fixed bug on translate escape string
*  Added _ uninstall.php support

0.8 - 2009.08.16
===========================================================
*  Fixed php 5.3 compatibility

translater 0.7
===========================================================
*  Fixed some l10n
*  Fixed ''xhtml strict'' validation

translater 0.6
===========================================================
*  Added ''author'' to langs files
*  Added ''two-cols'' option
*  Added ''sort option'' on array of translations
*  Fixed ''bugs'' with no theme
*  Fixed ''bugs'' with folder perms
*  Fixed ''nothing to update" in simple mode
*  Fixed ''bugs'' in Import/export
*  Fixed ''html &gt;'' like DC changset 2385
*  Fixed ''xhtml strict'' validation

translater 0.5
===========================================================
*  Fixed ''admin url''
*  Added user perm check
*  Fixed ''bugs'' with bad strings (close #166)

translater 0.4
===========================================================
*  Changed default tab to plugin
*  Replaced list of modules in select box rather than in help
*  Fixed wrong message when nothing to export
*  Added help in helpBlock
