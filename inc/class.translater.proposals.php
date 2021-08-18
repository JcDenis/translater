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


/**
 * Translater proposal tools container.
 */
class translaterProposals
{
	public $core;
	
	private $stack = array();
	
	public function __construct($core)
	{
		$this->core = $core;
		
		# --BEHAVIOR-- addTranslaterProposalTool
		$core->callBehavior('addTranslaterProposalTool',$this);
	}
	
	public function addTool($id)
	{
		if (!class_exists($id)) {
			return;
		}
		
		$r = new ReflectionClass($id);
		$p = $r->getParentClass();

		if (!$p || $p->name != 'translaterProposalTool') {
			return;
		}
		
		$this->stack[$id] = new $id($this->core);
	}
	
	public function getTools()
	{
		return $this->stack;
	}
	
	public function getTool($id)
	{
		return array_key_exists($id,$this->stack) ? $this->stack[$id] : null;
	}
	
	public function hasTool($id)
	{
		return array_key_exists($id,$this->stack);
	}
}
?>