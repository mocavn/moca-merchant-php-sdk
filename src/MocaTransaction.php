<?php

namespace Moca\Merchant;

class MocaTransaction
{

    private $amount;
    private $qrcode;
    private $currency;
    private $txType;
    private $partnerTxID;
    private $origPartnerTxID;
    private $additionalInfo;
    private $description;
    private $brandName;
    private $code;
    private $originTxID;
    private $refundPartnerTxID;
    private $access_token;
    private $state;

    private $environment;
    private $locale;
    private $partnerID;
    private $partnerSecret;
    private $merchantID;
    private $terminalID;
    private $clientID;
    private $clientSecret;
    private $redirectUrl;

    public function __construct(string $environment, string $locale, string $partnerID, string $partnerSecret, string $merchantID, string $terminalID, string $clientID, string $clientSecret, string $redirectUrl)
    {
        $this->environment = $environment;
        $this->locale = $locale;
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
            'partnerID'         => $this->getPartnerID(),
            'partnerSecret'     => $this->getPartnerSecret(),
            'merchantID'        => $this->getMerchantID(),
            'terminalID'        => $this->getTerminalID(),
            'clientID'          => $this->getClientID(),
            'clientSecret'      => $this->getClientSecret(),
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
            if (strtoupper($this->getLocale()) == 'VN') {
                $partnerInfo['url'] = 'https://partner-gw.moca.vn';
            } else {
                $partnerInfo['url'] = 'https://partner-api.grab.com';
            }
        } else {
            # This to get the which domain can runing on their country
            if (strtoupper($this->getLocale()) == 'VN') {
                $partnerInfo['url'] = 'https://stg-paysi.moca.vn';
            } else {
                $partnerInfo['url'] = 'https://partner-api.stg-myteksi.com';
            }
        }

        if (strtoupper($this->getLocale()) == 'VN') {
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

    /** 1. getRequest use for app to app
     * @param $partnerTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $brandName
     * @param mixed $isSync
     */
    public function getRequest() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'merchantID'        => $env['merchantID'],
                'description'       => $this->getDescription(),
                'isSync'            => false,
                'metaInfo'          => array("brandName"=>$this->getBrandName() != ''? $this->getBrandName() : '' ),
            );
            // This to get the which path can runing on their country
            return MocaRestClient::post($env, $env['chargeInit'], $requestBody, "ONLINE");

        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 1. createDeeplinkUrl use for web to app
     * @param $partnerTxID
     * @param $amount
     * @param $currency
     * @param $description
     * @param mixed $brandName
     * @param mixed $isSync
     */
    public function createDeeplinkUrl() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'merchantID'        => $env['merchantID'],
                'description'       => $this->getDescription(),
                'isSync'            => false,
                'metaInfo'          => array('brandName'=>$this->getBrandName()),
            );
            
            $resp = MocaRestClient::post($env, $env['chargeInit'], $requestBody, "ONLINE");
            // generation web url
            if ($resp->code == 200) {
                $bodyResp = $resp->body;
                $scope = 'payment.vn.one_time_charge';
                $codeChallenge = $this->base64URLEncode(base64_encode(hash('sha256', $this->base64URLEncode($this->getPartnerTxID().$this->getPartnerTxID()), true)));
                $resp->body = $env['url'] .'/grabid/v1/oauth2/authorize?acr_values=consent_ctx%3AcountryCode%3DVN,currency%3DVND&client_id='.$this->getClientID().
                        '&code_challenge='.$codeChallenge.'&code_challenge_method=S256&nonce='.$this->generateRandomString(16).
                        '&redirect_uri='.$this->getRedirectUrl().'&request='.$bodyResp->request.'&response_type=code&scope='.$scope.'&state='.$this->getState();
                return $resp;
            } else {
                return $resp;
            }
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 2. oAuthToken to get token to complete, check charge status and refund transaction
     * @param $partnerTxID
     * @param $clientID
     * @param $clientSecret
     * @param mixed $redirect_url
     * @param mixed $code
     */
    public function oAuthToken() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'grant_type'    => "authorization_code",
                'client_id'     => $env['clientID'],
                'client_secret' => $env['clientSecret'],
                'code_verifier' => $this->base64URLEncode($this->getPartnerTxID().$this->getPartnerTxID()),
                'redirect_uri'  => $this->getRedirectUrl(),
                'code'          => $this->getCode(),
            );
            return MocaRestClient::post($env, $env['OAuth2Token'], $requestBody, "ONLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. chargeComplete to finished transaction
     * @param $partnerTxID
     * @param $access_token
     */
    public function chargeComplete() {
        try {
            $env = $this->getpartnerInfo();
            $requestBodyChargeComplete = array(
                'partnerTxID'       => $this->getPartnerTxID(),
            );
            
            return MocaRestClient::post($env, $env['chargeComplete'], $requestBodyChargeComplete, "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. getChargeStatus to check status end of transaction
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function getChargeStatus() {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$this->getPartnerTxID(),$env['onaChargeStatus']);
            $url = str_replace("money",$this->getCurrency(),$url);

            return MocaRestClient::get($env, $url, "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 5. RefundTxn to refund transaction
     * @param $partnerTxID
     * @param $currency
     * @param $amount
     * @param $description
     * @param @originTxID
     * @param $access_token
     */
    public function refundTxnOnA() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency(),
                'merchantID'        => $env['merchantID'],
                'description'       => $this->getDescription(),
                'originTxID'        => $this->getOriginTxID(),
            );
            
            return MocaRestClient::post($env, $env['onaRefundTxn'], $requestBody, "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 6. getRefundStatus to check status end of transaction
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function getRefundStatus() {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$this->getPartnerTxID(),$env['onaCheckRefundTxn']);
            $url = str_replace("money",$this->getCurrency(),$url);

            return MocaRestClient::get($env, $url,'application/json', "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 7. getOtcStatus to get OAuthCode
     * @param $partnerTxID
     * @param $currency
     * @param $access_token
     */
    public function getOtcStatus() {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$this->getPartnerTxID(),$env['oneTimeChargeStatus']);
            $url = str_replace("money",$this->getCurrency(),$url);
            
            return MocaRestClient::get($env, $url,'application/json', "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // All api below for POS integration
    /** 1. createQrCode to Create QR code for POS
     * @param $partnerTxID
     * @param $currency
     * @param $amount
     */
    public function createQrCode() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'partnerTxID' => $this->getPartnerTxID()
            );
            
            $resp = MocaRestClient::post($env, $env['createQrCode'], $requestBody, "OFFLINE");

            if ($resp->code == 200) {
                $this->setOriginTxID($resp->body->TxID);
                $this->setOrigPartnerTxID($this->getPartnerTxID());
            }

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 2. cancelTxn if user QR do not scan or expire
     * @param $partnerTxID
     * @param $currency
     * @param $originTxID
     */
    public function cancelTxn() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $this->getCurrency(),
                'origTxID' => $this->getOriginTxID(),
                'partnerTxID' => $this->getPartnerTxID()
            );
            
            return MocaRestClient::put($env, str_replace("OriginTxID",$this->getOriginTxID(),$env['cancelQrTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. refundPosTxn to refund transaction already success.
     * @param $partnerTxID
     * @param $currency
     * @param $originTxID
     * @param $amount
     * @param $description
     */
    public function refundPosTxn() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'origTxID' => $this->getOriginTxID(),
                'amount'   => $this->getAmount(),
                'reason'    => $this->getDescription(),
                'partnerTxID' => $this->getPartnerTxID()
            );
            
            return MocaRestClient::put($env, str_replace("OriginTxID",$this->getOriginTxID(),$env['posRefundTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. performTxn use if method is CPQR
     * @param $partnerTxID
     * @param $currency
     * @param $code
     * @param $amount
     */
    public function performTxn() {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'partnerTxID' => $this->getPartnerTxID(),
                'code' => $this->getCode(),
            );
            
            return MocaRestClient::post($env, $env['performTxn'], $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 
    /** 5. Get charge status use if method is CPQR
     * @param $partnerTxID
     * @param $currency
     */
    public function posChargeStatus() {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("PartnerTxID",$this->getPartnerTxID(),$env['posChargeStatus']);
            $url = str_replace("money",$this->getCurrency(),$url);
            
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
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @param mixed $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRefundPartnerTxID()
    {
        return $this->refundPartnerTxID;
    }

    /**
     * @param mixed $refundPartnerTxID
     */
    public function setRefundPartnerTxID($refundPartnerTxID)
    {
        $this->refundPartnerTxID = $refundPartnerTxID;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginTxID()
    {
        return $this->originTxID;
    }

    /**
     * @param mixed $originTxID
     */
    public function setOriginTxID($originTxID)
    {
        $this->originTxID = $originTxID;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQrcode()
    {
        return $this->qrcode;
    }

    /**
     * @param mixed $qrcode
     */
    public function setQrcode($qrcode)
    {
        $this->qrcode = $qrcode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTxType()
    {
        return $this->txType;
    }

    /**
     * @param mixed $txType
     */
    public function setTxType($txType)
    {
        $this->txType = $txType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPartnerTxID()
    {
        return $this->partnerTxID;
    }

    /**
     * @param mixed $partnerTxID
     */
    public function setPartnerTxID($partnerTxID)
    {
        $this->partnerTxID = $partnerTxID;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    /**
     * @param mixed $additionalInfo
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brandName;
    }

    /**
     * @param mixed $brandName
     */
    public function setBrandName($brandName)
    {
        $this->brandName = $brandName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrigPartnerTxID()
    {
        return $this->origPartnerTxID;
    }

    /**
     * @param mixed $origPartnerTxID
     */
    public function setOrigPartnerTxID($origPartnerTxID)
    {
        $this->origPartnerTxID = $origPartnerTxID;
        return $this;
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
    public function getLocale()
    {
        return $this->locale;
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
