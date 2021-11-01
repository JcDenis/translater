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
 * Google proposal tool.
 *
 * This uses google API v2 to translate strings
 */
class googleProposalTool extends translaterProposalTool
{
    private $api   = 'https://www.googleapis.com/language/translate/v2';
    private $agent = 'dcTranslater - http://jcd.lv/?q=translater';
    private $key   = null; //ex: AsSDqsGsfdSDSQFQsfedj9bnzY390aIg-1d

    protected function setup()
    {
        $this->key = $this->core->blog->settings->translater->translater_google_proposal_key;

        $this->setName(__('Google'));
        $this->setDesc(__('Google Translation Tool API'));
        $this->setActive(!empty($this->key));
    }

    public function form()
    {
        return
        '<p><label class="classic" for="translater_google_proposal_key">' .
        __('API Console Single Access Key') . '<br />' .
        form::field('translater_google_proposal_key', 65, 255, $this->key) .
        '</label></p>' .
        '<p>' . __('You must have on Google API console:') . '</p>' .
        '<ul>' .
        '<li><a href="https://code.google.com/apis/console/#access">' . __('A single access API key') . '</a></li>' .
        '<li><a href="https://code.google.com/apis/console/#services">' . __('Activate the "translate API" service') . '</a></li>' .
        '</ul>';
    }

    public function save()
    {
        $key = empty($_POST['translater_google_proposal_key']) ?
            '' : $_POST['translater_google_proposal_key'];

        $this->core->blog->settings->translater->put('translater_google_proposal_key', $key, 'string', '', true, true);
    }

    public function translate($str, $from, $to)
    {
        try {
            $data = [
                'key'    => $this->key,
                'q'      => $str,
                'source' => $from,
                'target' => $to
            ];

            $path   = '';
            $client = netHttp::initClient($this->api, $path);
            $client->setUserAgent($this->agent);
            $client->useGzip(false);
            $client->setPersistReferers(false);
            $client->get($path, $data);

            $rs = $client->getContent();

            if ($client->getStatus() != 200) {
                throw new Exception(__('Failed to query service.'));
            }

            if (null === ($dec = json_decode($rs))) {
                throw new Exception('Failed to decode result');
            }

            if ('' == @$dec->data->translations[0]->translatedText) {
                throw new Exception('No data response');
            }

            return $dec->data->translations[0]->translatedText;
        } catch (Exception $e) {
        }

        return '';
    }
}
