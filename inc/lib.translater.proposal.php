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
 * Translater proposal tool.
 *
 * Generic class to provide translation tool
 */
abstract class translaterProposalTool
{
    private $active = false;
    private $name   = 'unknow';
    private $desc   = 'no description';

    /**
    Constructor
     */
    final public function __construct()
    {
        $this->setup();
    }

    /**
    Set name of this tool

    @param  string  $name   Tool's name
     */
    final protected function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
    Get name of this tool

    @return string  Tool's name
     */
    final public function getName()
    {
        return $this->name;
    }

    /**
    Set description of this tool

    @param  string  $desc   Tool's description
     */
    final protected function setDesc($desc)
    {
        $this->desc = (string) $desc;
    }

    /**
    Get description of this tool

    @return string  Tool's description
     */
    final public function getDesc()
    {
        return $this->desc;
    }

    /**
    Set tool as (un)active

    @param  boolean     $active     True to set it as active
     */
    final protected function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
    Check if this tool is active

    @return boolean True if it is active
     */
    final public function isActive()
    {
        return $this->active;
    }

    /**
    Set tool's info - using setName(),setDesc(),setActive()
     */
    abstract protected function setup();

    /**
    Get configuration interface

    @return string  Form field
     */
    abstract public function form();

    /**
    Save configuration
     */
    abstract public function save();

    /**
    Translate a string from a language to another

    @param  string  $str    Trimed UTF-8 string to translate
    @param  string  $from   Source language code
    @param  string  $to     Destination language code
    @return string  Translated string
     */
    abstract public function translate($str, $from, $to);
}
