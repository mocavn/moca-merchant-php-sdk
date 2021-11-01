<?php

namespace Moca\Merchant;

class MocaTransaction
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

    public function __construct(string $environment, string $country, string $partnerID, string $partnerSecret, string $merchantID, string $terminalID, string $clientID, string $clientSecret, string $redirectUrl)
    {
        $this->environment = $environment;
        $this->country = $country;
        $this->partnerID = $partnerID;
        $this->partnerSecret = $partnerSecret;
        $this->merchantID = $merchantID;
        $this->terminalID = $terminalID;
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
            $partnerInfo['onaChargeStatus'] = "/mocapay/partner/v2/charge/PartnerTxID/status?currency=money";
            $partnerInfo['onaRefundTxn'] = "/mocapay/partner/v2/refund";
            $partnerInfo['onaCheckRefundTxn'] = "/mocapay/partner/v2/refund/PartnerTxID/status?currency=money";
            $partnerInfo['oneTimeChargeStatus'] = "/mocapay/partner/v2/one-time-charge/PartnerTxID/status?currency=money";
            # offline path
            $partnerInfo['createQrCode'] = "/mocapay/partners/v1/terminal/qrcode/create";
            $partnerInfo['cancelQrTxn'] = "/mocapay/partners/v1/terminal/transaction/OriginTxID/cancel";
            $partnerInfo['posRefundTxn'] = "/mocapay/partners/v1/terminal/transaction/OriginTxID/refund";
            $partnerInfo['performTxn'] = "/mocapay/partners/v1/terminal/transaction/perform";
            $partnerInfo['posChargeStatus'] = "/mocapay/partners/v1/terminal/transaction/PartnerTxID?currency=money&txType=P2M";
        } else {
            # online path
            $partnerInfo['chargeInit'] = "/grabpay/partner/v2/charge/init";
            $partnerInfo['OAuth2Token'] = "/grabid/v1/oauth2/token";
            $partnerInfo['chargeComplete'] = "/grabpay/partner/v2/charge/complete";
            $partnerInfo['onaChargeStatus'] = "/grabpay/partner/v2/charge/PartnerTxID/status?currency=money";
            $partnerInfo['onaRefundTxn'] = "/grabpay/partner/v2/refund";
            $partnerInfo['onaCheckRefundTxn'] = "/grabpay/partner/v2/refund/PartnerTxID/status?currency=money";
            $partnerInfo['oneTimeChargeStatus'] = "/grabpay/partner/v2/one-time-charge/PartnerTxID/status?currency=money";
            # offline path
            $partnerInfo['createQrCode'] = "/grabpay/partners/v1/terminal/qrcode/create";
            $partnerInfo['cancelQrTxn'] = "/grabpay/partners/v1/terminal/transaction/OriginTxID/cancel";
            $partnerInfo['posRefundTxn'] = "/grabpay/partners/v1/terminal/transaction/OriginTxID/refund";
            $partnerInfo['performTxn'] = "/grabpay/partners/v1/terminal/transaction/perform";
            $partnerInfo['posChargeStatus'] = "/grabpay/partners/v1/terminal/transaction/PartnerTxID?currency=money&txType=P2M";
        }

        return $partnerInfo;
    } 

    /** 1. apiChargeInit use for app to app
     * @param $partnerTxID
     * @param $partnerGroupTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $brandName
     * @param mixed $isSync
     */
    public function apiChargeInit($partnerTxID, $partnerGroupTxID, $amount, $currency, $description, $isSync = false, $brandName) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $partnerTxID,
                'partnerGroupTxID'  => $partnerGroupTxID,
                'amount'            => $amount,
                'currency'          => $currency,
                'merchantID'        => $env['merchantID'],
                'description'       => $description,
                'isSync'            => $isSync,
                'metaInfo'          => array("brandName"=>$brandName != ''? $brandName : '' ),
            );
            // This to get the which path can runing on their country
            return MocaRestClient::post($env, $env['chargeInit'], $requestBody, "ONLINE");

        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 1. apiCreateDeeplinkUrl use for web to app
     * @param $partnerTxID
     * @param $partnerGroupTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $brandName
     * @param mixed $isSync
     * @param $state
     */
    public function apiCreateDeeplinkUrl($partnerTxID, $partnerGroupTxID, $amount, $currency, $description, $isSync = false, $brandName, $state) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $partnerTxID,
                'partnerGroupTxID'  => $partnerGroupTxID,
                'amount'            => $amount,
                'currency'          => $currency,
                'merchantID'        => $env['merchantID'],
                'description'       => $description,
                'isSync'            => $isSync,
                'metaInfo'          => array("brandName"=>$brandName != ''? $brandName : '' ),
            );
            
            $resp = MocaRestClient::post($env, $env['chargeInit'], $requestBody, "ONLINE");
            // generation web url
            if ($resp->code == 200) {
                $bodyResp = $resp->body;
                $scope = 'payment.vn.one_time_charge';
                $codeChallenge = $this->base64URLEncode(base64_encode(hash('sha256', $this->base64URLEncode($partnerTxID.$partnerTxID), true)));
                $resp->body = $env['url'] .'/grabid/v1/oauth2/authorize?acr_values=consent_ctx%3AcountryCode%3DVN,currency%3DVND&client_id='.$this->getClientID().
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

    /** 2. apiOAuthToken to get token to complete, check charge status and refund transaction
     * @param $partnerTxID\
     * @param $code
     */
    public function apiOAuthToken($partnerTxID,$code) {
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
            return MocaRestClient::post($env, $env['OAuth2Token'], $requestBody, "ONLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. apiChargeComplete to finished transaction
     * @param $partnerTxID
     * @param $access_token
     */
    public function apiChargeComplete($partnerTxID, $access_token) {
        try {
            $env = $this->getpartnerInfo();
            $requestBodyChargeComplete = array(
                'partnerTxID'       => $partnerTxID,
            );
            
            return MocaRestClient::post($env, $env['chargeComplete'], $requestBodyChargeComplete, "ONLINE",$access_token);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. apiGetChargeStatus to check status end of transaction
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function apiGetChargeStatus($partnerTxID, $currency, $access_token) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$partnerTxID,$env['onaChargeStatus']);
            $url = str_replace("money",$currency,$url);

            return MocaRestClient::get($env, $url, "ONLINE",$access_token);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 5. apiRefundTxnOnA to refund transaction
     * @param $partnerTxID
     * @param $partnerGroupTxID
     * @param $currency
     * @param $amount
     * @param $description
     * @param $originTxID
     * @param $access_token
     */
    public function apiRefundTxnOnA($partnerTxID, $partnerGroupTxID, $currency, $amount, $description, $originTxID, $access_token) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $partnerTxID,
                'partnerGroupTxID'  => $partnerGroupTxID,
                'amount'            => $amount,
                'currency'          => $currency,
                'merchantID'        => $env['merchantID'],
                'description'       => $description,
                'originTxID'        => $originTxID,
            );
            
            return MocaRestClient::post($env, $env['onaRefundTxn'], $requestBody, "ONLINE",$access_token);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 6. apiGetRefundStatus to check status end of transaction
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function apiGetRefundStatus($partnerTxID,$currency ='VND', $access_token) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$partnerTxID,$env['onaCheckRefundTxn']);
            $url = str_replace("money",$currency,$url);

            return MocaRestClient::get($env, $url,'application/json', "ONLINE",$access_token);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 7. apiGetOtcStatus to get OAuthCode
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function apiGetOtcStatus($partnerTxID,$currency, $access_token) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$partnerTxID,$env['oneTimeChargeStatus']);
            $url = str_replace("money",$currency,$url);
            
            return MocaRestClient::get($env, $url,'application/json', "ONLINE",$access_token);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // All api below for POS integration
    /** 1. apiCreateQrCode to Create QR code for POS
     * @param $partnerTxID
     * @param $currency
     * @param $amount
     */
    public function apiCreateQrCode($partnerTxID, $amount, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $amount,
                'currency' => $currency,
                'partnerTxID' => $partnerTxID
            );
            
            $resp = MocaRestClient::post($env, $env['createQrCode'], $requestBody, "OFFLINE");

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 2. apiCancelTxn if user QR do not scan or expire
     * @param $partnerTxID
     * @param $currency
     * @param $originTxID
     */
    public function apiCancelTxn($partnerTxID, $currency, $originTxID) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $currency,
                'origTxID' => $originTxID,
                'partnerTxID' => $partnerTxID
            );
            
            return MocaRestClient::put($env, str_replace("OriginTxID",$originTxID,$env['cancelQrTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. apiRefundPosTxn to refund transaction already success.
     * @param $partnerTxID
     * @param $currency
     * @param $originTxID
     * @param $amount
     * @param $description
     */
    public function apiRefundPosTxn($partnerTxID, $currency, $originTxID, $amount, $description) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $currency,
                'origTxID' => $originTxID,
                'amount'   => $amount,
                'reason'    => $description,
                'partnerTxID' => $partnerTxID
            );
            
            return MocaRestClient::put($env, str_replace("OriginTxID",$originTxID,$env['posRefundTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. apiPerformTxn use if method is CPQR
     * @param $partnerTxID
     * @param $currency
     * @param $code
     * @param $amount
     */
    public function apiPerformTxn($partnerTxID, $currency, $amount, $code) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $amount,
                'currency' => $currency,
                'partnerTxID' => $partnerTxID,
                'code' => $code,
            );
            
            return MocaRestClient::post($env, $env['performTxn'], $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 
    /** 5. apiPosChargeStatus: Get charge status use if method is CPQR
     * @param $partnerTxID
     * @param $currency
     */
    public function apiPosChargeStatus($partnerTxID, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$partnerTxID,$env['posChargeStatus']);
            $url = str_replace("money",$currency,$url);
            
            return MocaRestClient::get($env, $url, '', "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }


    private function generateRandomString($length) {
        $text = '';
        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ( $i = 0; $i < $length; $i++) {
            $text .= $possible[rand(0, strlen($possible)-1)];
        }
        return $text;
    }

    private function base64URLEncode($str) {
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
