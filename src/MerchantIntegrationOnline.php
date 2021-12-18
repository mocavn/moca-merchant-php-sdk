<?php

namespace Moca\Merchant;

class MerchantIntegrationOnline
{
    private $environment;
    private $country;
    private $partnerID;
    private $partnerSecret;
    private $merchantID;
    private $terminalID;
    private $clientID;
    private $clientSecret;
    private $redirectUrl;

    public function __construct(string $environment, string $country, string $partnerID, string $partnerSecret, string $merchantID, string $clientID, string $clientSecret, string $redirectUrl)
    {
        $this->environment = $environment;
        $this->country = $country;
        $this->partnerID = $partnerID;
        $this->partnerSecret = $partnerSecret;
        $this->merchantID = $merchantID;
        $this->terminalID = '';
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl = $redirectUrl;
    }

    
    public function getpartnerInfo() {
        $partnerInfo = array(
            'sdkVersion'        => '1.0.0',
            'partnerID'         => $this->getPartnerID(),
            'partnerSecret'     => $this->getPartnerSecret(),
            'merchantID'        => $this->getMerchantID(),
            'terminalID'        => $this->getTerminalID(),
            'clientID'          => $this->getClientID(),
            'clientSecret'      => $this->getClientSecret(),
            'country'           => strtoupper($this->getCountry()),
            'url'               => '',
            'chargeInit'        => '',
            'OAuth2Token'       => '',
            'chargeComplete'    => '',
            'onaChargeStatus'   => '',
            'onaRefundTxn'      => '',
            'onaCheckRefundTxn' => '',
            'oneTimeChargeStatus' => '',
            'createQrCode'      => '',
            'cancelQrTxn'       => '',
            'posRefundTxn'      => '',
            'performTxn'        => '',
            'posChargeStatus'   => '',
        );

        if (strtoupper($this->getEnvironment()) == 'PRODUCTION') {
            # This to get the which domain can runing on their country
            if (strtoupper($this->getCountry()) == 'VN') {
                $partnerInfo['url'] = 'https://partner-gw.moca.vn';
            } else {
                $partnerInfo['url'] = 'https://partner-api.grab.com';
            }
        } else {
            # This to get the which domain can runing on their country
            if (strtoupper($this->getCountry()) == 'VN') {
                $partnerInfo['url'] = 'https://stg-paysi.moca.vn';
            } else {
                $partnerInfo['url'] = 'https://partner-api.stg-myteksi.com';
            }
        }

        if (strtoupper($this->getCountry()) == 'VN') {
            $partnerInfo['chargeInit'] = "/mocapay/partner/v2/charge/init";
            $partnerInfo['OAuth2Token'] = "/grabid/v1/oauth2/token";
            $partnerInfo['chargeComplete'] = "/mocapay/partner/v2/charge/complete";
            $partnerInfo['onaChargeStatus'] = "/mocapay/partner/v2/charge/{PartnerTxID}/status?currency={currency}";
            $partnerInfo['onaRefundTxn'] = "/mocapay/partner/v2/refund";
            $partnerInfo['onaCheckRefundTxn'] = "/mocapay/partner/v2/refund/{refundPartnerTxID}/status?currency={currency}";
            $partnerInfo['oneTimeChargeStatus'] = "/mocapay/partner/v2/one-time-charge/{PartnerTxID}/status?currency={currency}";
            # offline path
            $partnerInfo['createQrCode'] = "/mocapay/partners/v1/terminal/qrcode/create";
            $partnerInfo['cancelQrTxn'] = "/mocapay/partners/v1/terminal/transaction/{origPartnerTxID}/cancel";
            $partnerInfo['posRefundTxn'] = "/mocapay/partners/v1/terminal/transaction/{OriginTxID}/refund";
            $partnerInfo['performTxn'] = "/mocapay/partners/v1/terminal/transaction/perform";
            $partnerInfo['posChargeStatus'] = "/mocapay/partners/v1/terminal/transaction/{PartnerTxID}?currency={currency}&msgID={msgID}&txType=P2M";
            $partnerInfo['posChargeRefundStatus'] = "/mocapay/partners/v1/terminal/transaction/{refundPartnerTxID}?currency={currency}&msgID={msgID}&txType=Refund";
        } else {
            # online path
            $partnerInfo['chargeInit'] = "/grabpay/partner/v2/charge/init";
            $partnerInfo['OAuth2Token'] = "/grabid/v1/oauth2/token";
            $partnerInfo['chargeComplete'] = "/grabpay/partner/v2/charge/complete";
            $partnerInfo['onaChargeStatus'] = "/grabpay/partner/v2/charge/{PartnerTxID}/status?currency={currency}";
            $partnerInfo['onaRefundTxn'] = "/grabpay/partner/v2/refund";
            $partnerInfo['onaCheckRefundTxn'] = "/grabpay/partner/v2/refund/{refundPartnerTxID}/status?currency={currency}";
            $partnerInfo['oneTimeChargeStatus'] = "/grabpay/partner/v2/one-time-charge/{PartnerTxID}/status?currency={currency}";
            # offline path
            $partnerInfo['createQrCode'] = "/grabpay/partner/v1/terminal/qrcode/create";
            $partnerInfo['cancelQrTxn'] = "/grabpay/partner/v1/terminal/transaction/{origPartnerTxID}/cancel";
            $partnerInfo['posRefundTxn'] = "/grabpay/partner/v1/terminal/transaction/{OriginTxID}/refund";
            $partnerInfo['performTxn'] = "/grabpay/partner/v1/terminal/transaction/perform";
            $partnerInfo['posChargeStatus'] = "/grabpay/partner/v1/terminal/transaction/{PartnerTxID}?currency={currency}&msgID={msgID}&txType=P2M";
            $partnerInfo['posChargeRefundStatus'] = "/grabpay/partner/v1/terminal/transaction/{refundPartnerTxID}?currency={currency}&msgID={msgID}&txType=Refund";
        }

        return $partnerInfo;
    } 

    /** 1. onaChargeInit use for app to app
     * @param $partnerTxID
     * @param $partnerGroupTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $metaInfo
     * @param mixed $items
     * @param mixed $shippingDetails
     * @param mixed $isSync
     */
    public function onaChargeInit($partnerTxID, $partnerGroupTxID, $amount, $currency, $description, $isSync = false, array $metaInfo =[], array $items =[], array $shippingDetails = [], $hidePaymentMethods =[]) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $partnerTxID,
                'partnerGroupTxID'  => $partnerGroupTxID,
                'amount'            => $amount,
                'currency'          => $currency,
                'merchantID'        => $env['merchantID'],
                'description'       => $description,
                'isSync'            => $isSync
            );
            if(!empty($hidePaymentMethods)) {
                $requestBody = array_merge($requestBody,array('hidePaymentMethods' => $hidePaymentMethods));
            }
            if(!empty($metaInfo)) {
                $requestBody = array_merge($requestBody, array('metaInfo' => $metaInfo));
            }
            if(!empty($items)) {
                $requestBody = array_merge($requestBody, array('items' => $items));
            }
            if(!empty($shippingDetails)) {
                $requestBody = array_merge($requestBody, array('shippingDetails' => $shippingDetails));
            }
            // This to get the which path can runing on their country
            return RestClient::post($env, $env['chargeInit'], $requestBody, "ONLINE");

        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 1. onaCreateWebUrl use for web to app
     * @param $partnerTxID
     * @param $partnerGroupTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $metaInfo
     * @param mixed $items
     * @param mixed $shippingDetails
     * @param mixed $isSync
     * @param $state
     */
    public function onaCreateWebUrl($partnerTxID, $partnerGroupTxID, $amount, $currency, $description, $isSync = false, array $metaInfo =[], array $items =[], array $shippingDetails = [], $hidePaymentMethods = [], $state = null) {
        try {
            $env = $this->getpartnerInfo();
            $resp = $this->onaChargeInit($partnerTxID, $partnerGroupTxID, $amount, $currency, $description, $isSync, $metaInfo, $items, $shippingDetails, $hidePaymentMethods);
            
            // generation web url
            if ($resp->code == 200 && !empty($state)) {
                $bodyResp = $resp->body;
                if(strtoupper($this->getCountry()) == 'VN') {
                    $scope = 'payment.vn.one_time_charge';
                } else {
                    $scope = 'openid+payment.one_time_charge';
                }
                
                $codeChallenge = $this->base64URLEncode(base64_encode(hash('sha256', $this->base64URLEncode($partnerTxID.$partnerTxID), true)));
                $resp->body = $env['url'] .'/grabid/v1/oauth2/authorize?acr_values=consent_ctx%3AcountryCode%3D'.strtoupper($this->getCountry()).',currency%3D'.$currency.'&client_id='.$this->getClientID().
                        '&code_challenge='.$codeChallenge.'&code_challenge_method=S256&nonce='.$this->generateRandomString(16).
                        '&redirect_uri='.$this->getRedirectUrl().'&request='.$bodyResp->request.'&response_type=code&scope='.$scope.'&state='.$state;
                return $resp;
            } else {
                return $resp;
            }
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 2. onaOAuth2Token to get token to complete, check charge status and refund transaction
     * @param $partnerTxID
     * @param $code
     */
    public function onaOAuth2Token($partnerTxID, $code) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'grant_type'    => "authorization_code",
                'client_id'     => $env['clientID'],
                'client_secret' => $env['clientSecret'],
                'code_verifier' => $this->base64URLEncode($partnerTxID.$partnerTxID),
                'redirect_uri'  => $this->getRedirectUrl(),
                'code'          => $code,
            );
            return RestClient::post($env, $env['OAuth2Token'], $requestBody, "ONLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. onaChargeComplete to finished transaction
     * @param $partnerTxID
     * @param $accessToken
     */
    public function onaChargeComplete($partnerTxID, $accessToken) {
        try {
            $env = $this->getpartnerInfo();
            $requestBodyChargeComplete = array(
                'partnerTxID'       => $partnerTxID,
            );
            
            return RestClient::post($env, $env['chargeComplete'], $requestBodyChargeComplete, "ONLINE",$accessToken);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. onaGetChargeStatus to check status end of transaction
     * @param $partnerTxID
     * @param $currency
     * @param $accessToken
     */
    public function onaGetChargeStatus($partnerTxID, $currency, $accessToken) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("{PartnerTxID}",$partnerTxID,$env['onaChargeStatus']);
            $url = str_replace("{currency}",$currency,$url);

            return RestClient::get($env, $url, 'application/json', "ONLINE",$accessToken);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 5. onaRefund to refund transaction
     * @param $refundPartnerTxID
     * @param $partnerGroupTxID
     * @param $currency
     * @param $amount
     * @param $description
     * @param $txID  get from onaChargeComplete
     * @param $accessToken
     */
    public function onaRefund($refundPartnerTxID, $partnerGroupTxID, $amount, $currency, $txID, $description, $accessToken) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $refundPartnerTxID,
                'partnerGroupTxID'  => $partnerGroupTxID,
                'amount'            => intval($amount),
                'currency'          => $currency,
                'merchantID'        => $env['merchantID'],
                'description'       => $description,
                'originTxID'        => $txID,
            );
            
            return RestClient::post($env, $env['onaRefundTxn'], $requestBody, "ONLINE",$accessToken);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 6. onaGetRefundStatus to check status end of transaction
     * @param $refundPartnerTxID
     * @param $currency
     * @param $accessToken
     */
    public function onaGetRefundStatus($refundPartnerTxID, $currency ='VND', $accessToken) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("{refundPartnerTxID}", $refundPartnerTxID, $env['onaCheckRefundTxn']);
            $url = str_replace("{currency}", $currency, $url);

            return RestClient::get($env, $url, 'application/json', "ONLINE",$accessToken);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 7. onaGetOTCStatus to get OAuthCode
     * @param $partnerTxID
     * @param $currency
     */
    public function onaGetOTCStatus($partnerTxID, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("{PartnerTxID}",$partnerTxID,$env['oneTimeChargeStatus']);
            $url = str_replace("{currency}",$currency,$url);
            
            return RestClient::get($env, $url,'application/json', "ONLINE",'');
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    public function generateRandomString($length) {
        $text = '';
        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ( $i = 0; $i < $length; $i++) {
            $text .= $possible[rand(0, strlen($possible)-1)];
        }
        return $text;
    }

    public function base64URLEncode($str) {
        return str_replace(['=', '+', '/'], ['', '-', '_'], ($str));
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getPartnerID()
    {
        return $this->partnerID;
    }

    /**
     * @return mixed
     */
    public function getPartnerSecret()
    {
        return $this->partnerSecret;
    }

    /**
     * @return mixed
     */
    public function getMerchantID()
    {
        return $this->merchantID;
    }

    /**
     * @return mixed
     */
    public function getTerminalID()
    {
        return $this->terminalID;
    }

    /**
     * @return mixed
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
