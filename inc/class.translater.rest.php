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

/**
 * Translater REST service.
 *
 * Admin service de retrieve translation of a string
 * Queries come from translater jquery tools
 */
class translaterRest
{
    public static function getProposal($get)
    {
        $from   = !empty($get['langFrom']) ? trim($get['langFrom']) : '';
        $to     = !empty($get['langTo']) ? trim($get['langTo']) : '';
        $tool   = !empty($get['langTool']) ? trim($get['langTool']) : '';
        $str_in = !empty($get['langStr']) ? trim($get['langStr']) : '';

        $str_in  = text::toUTF8($str_in);
        $str_in  = trim($str_in);
        $str_out = '';

        $rsp = new xmlTag();

        try {
            if (empty($from) || empty($to) || empty($tool)) {
                throw new Exception(__('Missing params'));
            }

            $translater = new dcTranslater();

            if (!empty($str_in)) {
                if (!$translater->proposal->hasTool($tool)) {
                    throw new Exception(__('Failed to get translation tool'));
                }
                if (!$translater->proposal->getTool($tool)->isActive()) {
                    throw new Exception(__('Translation tool is not configured'));
                }

                $str_out = (string) $translater->proposal->getTool($tool)->translate($str_in, $from, $to);
            }

            $x            = new xmlTag('proposal');
            $x->lang_from = $from;
            $x->lang_to   = $to;
            $x->tool      = $tool;
            $x->str_from  = $str_in;
            $x->str_to    = text::toUTF8(html::decodeEntities($str_out));
            $rsp->insertNode($x);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return $rsp;
    }
}
