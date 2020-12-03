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
    private $status;
    private $updated;
    private $qrExpiryTime;
    private $deeplinkUrl;
    private $description;
    private $brandName;
    private $token;
    private $codeVerifier;
    private $partnerGroupTxID;
    private $code;
    private $originTxID;

    // 1. getRequest use for app to app
    public function getRequest() {
        try {
            $requestBody = array(
                'partnerTxID'       => self::getPartnerTxID(),
                'partnerGroupTxID'  => self::getPartnerGroupTxID(),
                'amount'            => self::getAmount(),
                'currency'          => self::getCurrency(),
                'merchantID'        => getenv('MOCA_MERCHANT_GRAB_ID'),
                'description'       => self::getDescription(),
                'isSync'            => false,
                'metaInfo'          => array("brandName"=>self::getBrandName()),
            );

            return MocaRestClient::post("/mocapay/partner/v2/charge/init", $requestBody);

        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 1. createDeeplinkUrl use for web to app
    public function createDeeplinkUrl() {
        try {
            $requestBody = array(
                'partnerTxID'       => self::getPartnerTxID(),
                'partnerGroupTxID'  => self::getPartnerGroupTxID(),
                'amount'            => self::getAmount(),
                'currency'          => self::getCurrency(),
                'merchantID'        => getenv('MOCA_MERCHANT_GRAB_ID'),
                'description'       => self::getDescription(),
                'isSync'            => false,
                'metaInfo'          => array('brandName'=>self::getBrandName()),
            );

            $resp = MocaRestClient::post("/mocapay/partner/v2/charge/init", $requestBody);  

            if ($resp->code == 200) {
                $bodyResp = $resp->body;
                $scope = 'payment.vn.one_time_charge';
                $codeVerifier = self::base64URLEncode(self::generateRandomString(64));
                $codeChallenge = self::base64URLEncode(hash('sha256', $codeVerifier));
                self::setCodeVerifier($codeChallenge);
                return MocaRestClient::apiEndpoint() .'/grabid/v1/oauth2/authorize?acr_values=consent_ctx%3AcountryCode%3DVN,currency%3DVND&client_id='.getenv('MOCA_MERCHANT_CLIENT_ID').
                    '&code_challenge='.$codeChallenge.'&code_challenge_method=S256&nonce='.self::generateRandomString(16).
                    '&redirect_uri='.getenv('MOCA_MERCHANT_REDIRECT_URI').'&request='.$bodyResp->request.'&response_type=code&scope='.$scope.'&state='.self::generateRandomString(7);
            } else {
                return $resp;
            }
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 2. oAuthToken to get token to complete, check charge status and refund transaction
    public function oAuthToken() {
        try {
            $requestBody = array(
                'grant_type'    => "authorization_code",
                'client_id'     => getenv('MOCA_MERCHANT_CLIENT_ID'),
                'client_secret' => getenv('MOCA_MERCHANT_CLIENT_SECRET'),
                'code_verifier' => self::getCodeVerifier(),
                'redirect_uri'  => getenv('MOCA_MERCHANT_REDIRECT_URI'),
                'code'          => self::getCode(),
            );

            $resp = MocaRestClient::post("/grabid/v1/oauth2/token", $requestBody);
            $obj = json_decode($resp);

            if ($obj->{'access_token'} != "") {
                self::setCodeVerifier($obj->{'access_token'});
            }

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 3. chargeComplete to finished transaction
    public function chargeComplete() {
        try {
            $requestBody = array(
                'partnerTxID'       => self::getPartnerTxID(),
            );

            $resp =MocaRestClient::post("/mocapay/partner/v2/charge/complete", $requestBody);
            $obj = json_decode($resp);

            if ($obj->{'status'} == 'success') {
                self::setOriginTxID(self::getPartnerTxID());
            }
            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 4. getChargeStatus to check status end of transaction
    public function getChargeStatus() {
        try {
            $uri = 'mocapay/partner/v2/charge/'.self::getPartnerTxID().'/status?currency='.self::getCurrency();

            return MocaRestClient::get($uri,'application/json');
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 5. RefundTxn to refund transaction
    public function RefundTxn() {
        try {
            $requestBody = array(
                'partnerTxID'       => self::getPartnerTxID(),
                'partnerGroupTxID'  => self::getPartnerGroupTxID(),
                'amount'            => self::getAmount(),
                'currency'          => self::getCurrency(),
                'merchantID'        => getenv('MOCA_MERCHANT_MERCHANT_ID'),
                'description'       => self::getDescription(),
                'originTxID'        => self::getOriginTxID(),
            );

            return MocaRestClient::post("/mocapay/partner/v2/refund", $requestBody);
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 6. getRefundStatus to check status end of transaction
    public function getRefundStatus() {
        try {
            $uri = 'mocapay/partner/v2/refund/'.self::getPartnerTxID().'/status?currency='.self::getCurrency();

            return MocaRestClient::get($uri,'application/json');
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
    }

    /**
     * @return mixed
     */
    public function getPartnerGroupTxID()
    {
        return $this->partnerGroupTxID;
    }

    /**
     * @param mixed $partnerGroupTxID
     */
    public function setPartnerGroupTxID($partnerGroupTxID)
    {
        $this->partnerGroupTxID = $partnerGroupTxID;
    }

    /**
     * @return mixed
     */
    public function getCodeVerifier()
    {
        return $this->codeVerifier;
    }/**
     * @param mixed $codeVerifier
     */
    public function setCodeVerifier($codeVerifier)
    {
        $this->codeVerifier = $codeVerifier;
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
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return mixed
     */
    public function getQrExpiryTime()
    {
        return $this->qrExpiryTime;
    }

    /**
     * @param mixed $qrExpiryTime
     */
    public function setQrExpiryTime($qrExpiryTime)
    {
        $this->qrExpiryTime = $qrExpiryTime;
    }

    /**
     * @return mixed
     */
    public function getDeeplinkUrl()
    {
        return $this->deeplinkUrl;
    }

    /**
     * @param mixed $deeplinkUrl
     */
    public function setDeeplinkUrl($deeplinkUrl)
    {
        $this->deeplinkUrl = $deeplinkUrl;
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
    }


    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
