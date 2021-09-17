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

if (!defined('DC_RC_PATH')) {
    return;
}

$d = dirname(__FILE__) . '/inc/';

$__autoload['dcTranslater']           = $d . 'class.dc.translater.php';
$__autoload['translaterRest']         = $d . 'class.translater.rest.php';
$__autoload['translaterProposals']    = $d . 'class.translater.proposals.php';

$__autoload['translaterProposalTool'] = $d . 'lib.translater.proposal.php';
$__autoload['googleProposalTool']     = $d . 'lib.translater.google.php';
$__autoload['microsoftProposalTool']  = $d . 'lib.translater.microsoft.php';