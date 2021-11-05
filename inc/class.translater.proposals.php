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
/**
 * Translater proposal tools container.
 */
class translaterProposals
{
    public $core;

    private $stack = [];

    public function __construct($core)
    {
        $this->core = $core;

        # --BEHAVIOR-- addTranslaterProposalTool
        $core->callBehavior('addTranslaterProposalTool', $this);
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
        return array_key_exists($id, $this->stack) ? $this->stack[$id] : null;
    }

    public function hasTool($id)
    {
        return array_key_exists($id, $this->stack);
    }
}
