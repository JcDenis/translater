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
    return;
}

$core->blog->settings->addNamespace('translater');
$core->addBehavior('adminModulesListGetActions', ['translaterAdminBehaviors', 'adminModulesGetActions']);
$core->addBehavior('adminModulesListDoActions', ['translaterAdminBehaviors', 'adminModulesDoActions']);
$core->addBehavior('adminDashboardFavorites', ['translaterAdminBehaviors', 'adminDashboardFavorites']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addGoogleProposalTool']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addYahooProposalTool']);
$core->addBehavior('addTranslaterProposalTool', ['translaterAdminBehaviors', 'addMicrosoftProposalTool']);
$core->rest->addFunction('getProposal', ['translaterRest', 'getProposal']);

$_menu['Plugins']->addItem(
    __('Translater'),
    $core->adminurl->get('admin.plugin.translater'),
    dcPage::getPF('translater/icon.png'),
    preg_match(
        '/' . preg_quote($core->adminurl->get('admin.plugin.translater')) . '(&.*)?$/', 
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->isSuperAdmin()
);

class translaterAdminBehaviors
{
    /**
     * Add button to go to module translation
     * 
     * @param  object $list     adminModulesList instance
     * @param  string $id       Module id
     * @param  arrray $prop     Module properties
     * @return string           HTML submit button
     */
    public static function adminModulesGetActions(adminModulesList $list, string $id, array $prop): ?string
    {
        if ($list->getList() != $prop['type'] . '-activate' 
            || !$list->core->blog->settings->translater->get('translater_' . $prop['type'] . '_menu')
            || !$list->core->auth->isSuperAdmin()
        ) {
            return null;
        }

        return 
            ' <input type="submit" name="translater[' . 
            html::escapeHTML($id) . 
            ']" value="' . _('Translate') . '" /> ';
    }

    /**
     * Redirect to module translation
     * 
     * @param  adminModulesList     $list       adminModulesList instance
     * @param  array                $modules    Selected modules ids
     * @param  string               $type       List type (plugin|theme)
     */
    public static function adminModulesDoActions(adminModulesList $list, array $modules, string $type)
    {
        if (empty($_POST['translater']) || !is_array($_POST['translater'])) {
            return null;
        }

        $list->core->adminurl->redirect(
            'admin.plugin.translater', 
            ['part' => 'module', 'type' => $type, 'module' => key($_POST['translater'])],
            '#module-lang'
        );
    }

    /**
     * Add dashboard favorites icon
     * 
     * @param  dcCore       $core   dcCore instance
     * @param  dcFavorites  $favs   dcFavorites instance
     */
    public static function adminDashboardFavorites(dcCore $core, dcFavorites$favs)
    {
        $favs->register('translater', [
            'title'       => __('Translater'),
            'url'         => $core->adminurl->get('admin.plugin.translater'),
            'small-icon'  => urldecode(dcPage::getPF('translater/icon.png')),
            'large-icon'  => urldecode(dcPage::getPF('translater/icon-big.png')),
            'permissions' => $core->auth->isSuperAdmin()
        ]);
    }

    /**
     * Register Google Translater tools in translate
     * 
     * @param translaterProposals $proposal translaterProposals instance
     */
    public static function addGoogleProposalTool(translaterProposals $proposal)
    {
        $proposal->addTool('googleProposalTool');
    }

    /**
     * Register Yahoo Babelfish tools in translater
     * 
     * @param translaterProposals $proposal translaterProposals instance
     */
    public static function addYahooProposalTool(translaterProposals $proposal)
    {
        $proposal->addTool('yahooProposalTool');
    }

    /**
     * Register Microsoft Bing tools in translater
     * 
     * @param translaterProposals $proposal translaterProposals instance
     */
    public static function addMicrosoftProposalTool(translaterProposals $proposal)
    {
        $proposal->addTool('microsoftProposalTool');
    }
}