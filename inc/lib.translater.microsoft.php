<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of translater, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2016 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

/**
 * Microsoft proposal tool.
 *
 * This uses Microsoft API to translate strings
 */
class microsoftProposalTool extends translaterProposalTool
{
    private $client = null; //ex: b6057813-234b-4154-b324-6342c27f608f
    private $secret = null; //ex: DsdDScn/+xdSFF1GDxdx1wbkKPphAfAVSH5VXDBVDI=

    protected function setup()
    {
        $this->setActive(false);
        $this->client = $this->core->blog->settings->translater->translater_microsoft_proposal_client;
        $this->secret = $this->core->blog->settings->translater->translater_microsoft_proposal_secret;
        
        $this->setName(__('Bing'));
        $this->setDesc(__('Microsoft Bing translation tool'));
        $this->setActive(!empty($this->client) && !empty($this->secret));
    }

    public function form()
    {
        return
        '<p><label class="classic" for="translater_microsoft_proposal_client">' . 
        __('Application client ID') . '<br />' . 
        form::field('translater_microsoft_proposal_client', 65, 255, $this->client) . 
        '</label></p>' . 
        '<p><label class="classic" for="translater_microsoft_proposal_secret">' . 
        __('Application client Secret') . '<br />' . 
        form::field('translater_microsoft_proposal_secret', 65, 255, $this->secret) . 
        '</label></p>' . 
        '<p>' . __('You must have:') . '</p>' . 
        '<ul>' . 
        '<li><a href="https://datamarket.azure.com/account">' . __('A Microsoft Windows Azure account') . '</a></li>' . 
        '<li><a href="https://datamarket.azure.com/dataset/bing/microsofttranslator">' . __('A valid subscription to Microsoft Translator') . '</a></li>' . 
        '<li><a href="https://datamarket.azure.com/developer/applications/">' . __('And register an application') . '</a></li>' . 
        '</ul>';
    }

    public function save()
    {
        $client = empty($_POST['translater_microsoft_proposal_client']) ? 
            '' : $_POST['translater_microsoft_proposal_client'];
        $secret = empty($_POST['translater_microsoft_proposal_secret']) ? 
            '' : $_POST['translater_microsoft_proposal_secret'];
        
        $this->core->blog->settings->translater->put('translater_microsoft_proposal_client', $client, 'string', '', true, true);
        $this->core->blog->settings->translater->put('translater_microsoft_proposal_secret', $secret, 'string', '', true, true);
    }

    public function translate($str, $from, $to)
    {
        try {
            return $this->doYourFuckingJob($this->client, $this->secret, $str, $from, $to);
        } catch (Exception $e) {}

        return '';
    }

    //
    // Microsoft fucking oAuth
    //

    private function doYourFuckingJob($client, $secret, $str, $from, $to)
    {
        try {
            //Client ID of the application.
            $clientID       = $client;
            //Client Secret key of the application.
            $clientSecret = $secret;
            //OAuth Url.
            $authUrl      = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
            //Application Scope Url
            $scopeUrl     = "http://api.microsofttranslator.com";
            //Application grant type
            $grantType    = "client_credentials";

            //Get the Access token.
            $accessToken  = $this->getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl);
            //Create the authorization Header string.
            $authHeader = "Authorization: Bearer " . $accessToken;

            //Set the params.//
            $fromLanguage = $from;
            $toLanguage   = $to;
            $inputStr     = $str;
            $contentType  = 'text/plain';
            $category     = 'general';

            $params = "text=" . urlencode($inputStr) . "&to=" . $toLanguage . "&from=" . $fromLanguage;
            $translateUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?$params";

            //Get the curlResponse.
            $curlResponse = $this->curlRequest($translateUrl, $authHeader);

            //Interprets a string of XML into an object.
            $xmlObj = simplexml_load_string($curlResponse);
            foreach((array)$xmlObj[0] as $val){
               $translatedStr = $val;
            }

            return (string) $translatedStr;
            /*
            echo "<table border=2px>";
            echo "<tr>";
            echo "<td><b>From $fromLanguage</b></td><td><b>To $toLanguage</b></td>";
            echo "</tr>";
            echo "<tr><td>" . $inputStr . "</td><td>" . $translatedStr . "</td></tr>";
            echo "</table>";
            */
        } catch (Exception $e) {
            throw $e;
        }
    }

    /*
     * Get the access token.
     *
     * @param string $grantType    Grant type.
     * @param string $scopeUrl     Application Scope URL.
     * @param string $clientID     Application client ID.
     * @param string $clientSecret Application client ID.
     * @param string $authUrl      Oauth Url.
     *
     * @return string.
     */
    private function getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl)
    {
        try {
            //Initialize the Curl Session.
            $ch = curl_init();
            //Create the request Array.
            $paramArr = array (
                 'grant_type'    => $grantType,
                 'scope'         => $scopeUrl,
                 'client_id'     => $clientID,
                 'client_secret' => $clientSecret
            );
            //Create an Http Query.//
            $paramArr = http_build_query($paramArr);
            //Set the Curl URL.
            curl_setopt($ch, CURLOPT_URL, $authUrl);
            //Set HTTP POST Request.
            curl_setopt($ch, CURLOPT_POST, TRUE);
            //Set data to POST in HTTP "POST" Operation.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
            //CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //Execute the  cURL session.
            $strResponse = curl_exec($ch);
            //Get the Error Code returned by Curl.
            $curlErrno = curl_errno($ch);
            if($curlErrno) {
                $curlError = curl_error($ch);
                curl_close($ch);
                throw new Exception($curlError);
            }
            //Close the Curl Session.
            curl_close($ch);
            //Decode the returned JSON string.
            $objResponse = json_decode($strResponse);
            if (@$objResponse->error){
                throw new Exception($objResponse->error_description);
            }
            return $objResponse->access_token;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /*
     * Create and execute the HTTP CURL request.
     *
     * @param string $url        HTTP Url.
     * @param string $authHeader Authorization Header string.
     * @param string $postData   Data to post.
     *
     * @return string.
     *
     */
    private function curlRequest($url, $authHeader) {
        //Initialize the Curl Session.
        $ch = curl_init();
        //Set the Curl url.
        curl_setopt ($ch, CURLOPT_URL, $url);
        //Set the HTTP HEADER Fields.
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array($authHeader,"Content-Type: text/xml"));
        //CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, False);
        //Execute the  cURL session.
        $curlResponse = curl_exec($ch);
        //Get the Error Code returned by Curl.
        $curlErrno = curl_errno($ch);
        if ($curlErrno) {
            $curlError = curl_error($ch);
            curl_close($ch);
            throw new Exception($curlError);
        }
        //Close a cURL session.
        curl_close($ch);
        return $curlResponse;
    }
}