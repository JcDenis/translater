<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of translater, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')){return;}

global $__autoload;

$__autoload['dcTranslater'] 			= dirname(__FILE__).'/inc/class.dc.translater.php';
$__autoload['translaterRest'] 		= dirname(__FILE__).'/inc/class.translater.rest.php';
$__autoload['translaterProposals'] 	= dirname(__FILE__).'/inc/class.translater.proposals.php';

$__autoload['translaterProposalTool'] 	= dirname(__FILE__).'/inc/lib.translater.proposal.php';
$__autoload['googleProposalTool'] 		= dirname(__FILE__).'/inc/lib.translater.google.php';
$__autoload['microsoftProposalTool'] 		= dirname(__FILE__).'/inc/lib.translater.microsoft.php';
?>