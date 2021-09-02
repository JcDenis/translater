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

global $__autoload;

$__autoload['dcTranslater'] = dirname(__FILE__) . '/inc/class.dc.translater.php';
$__autoload['translaterRest'] = dirname(__FILE__) . '/inc/class.translater.rest.php';
$__autoload['translaterProposals'] = dirname(__FILE__) . '/inc/class.translater.proposals.php';

$__autoload['translaterProposalTool'] = dirname(__FILE__) . '/inc/lib.translater.proposal.php';
$__autoload['googleProposalTool'] = dirname(__FILE__) . '/inc/lib.translater.google.php';
$__autoload['microsoftProposalTool'] = dirname(__FILE__) . '/inc/lib.translater.microsoft.php';