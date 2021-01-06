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

    // 1. getRequest use for app to app
    public function getRequest() {
        try {
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'merchantID'        => getenv('MOCA_MERCHANT_GRAB_ID'),
                'description'       => $this->getDescription(),
                'isSync'            => false,
                'metaInfo'          => array("brandName"=>$this->getBrandName() != ''? $this->getBrandName() : '' ),
            );

            return MocaRestClient::post("/mocapay/partner/v2/charge/init", $requestBody, "ONLINE");

        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 1. createDeeplinkUrl use for web to app
    public function createDeeplinkUrl() {
        try {
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'merchantID'        => getenv('MOCA_MERCHANT_GRAB_ID'),
                'description'       => $this->getDescription(),
                'isSync'            => false,
                'metaInfo'          => array('brandName'=>$this->getBrandName()),
            );

            $resp = MocaRestClient::post("/mocapay/partner/v2/charge/init", $requestBody, "ONLINE");

            if ($resp->code == 200) {
                $bodyResp = $resp->body;
                $scope = 'payment.vn.one_time_charge';
                $codeChallenge = $this->base64URLEncode(hash('sha256', $this->getPartnerTxID()));

                return MocaRestClient::apiEndpoint() .'/grabid/v1/oauth2/authorize?acr_values=consent_ctx%3AcountryCode%3DVN,currency%3DVND&client_id='.getenv('MOCA_MERCHANT_CLIENT_ID').
                    '&code_challenge='.$codeChallenge.'&code_challenge_method=S256&nonce='.$this->generateRandomString(16).
                    '&redirect_uri='.getenv('MOCA_MERCHANT_REDIRECT_URI').'&request='.$bodyResp->request.'&response_type=code&scope='.$scope.'&state='.$this->getState();
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
                'code_verifier' => $this->getPartnerTxID(),
                'redirect_uri'  => getenv('MOCA_MERCHANT_REDIRECT_URI'),
                'code'          => $this->getCode(),
            );

            $resp = MocaRestClient::post("/grabid/v1/oauth2/token", $requestBody, "ONLINE");

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 3. chargeComplete to finished transaction
    public function chargeComplete() {
        try {
            $requestBodyChargeComplete = array(
                'partnerTxID'       => $this->getPartnerTxID(),
            );

            return MocaRestClient::post("/mocapay/partner/v2/charge/complete", $requestBodyChargeComplete, "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 4. getChargeStatus to check status end of transaction
    public function getChargeStatus() {
        try {
            $uri = 'mocapay/partner/v2/charge/'.$this->getPartnerTxID().'/status?currency='.$this->getCurrency() != ''? $this->getCurrency(): 'VND';

            return MocaRestClient::get($uri,'application/json', "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 5. RefundTxn to refund transaction
    public function refundTxnOnA() {
        try {
            $requestBody = array(
                'partnerTxID'       => $this->getPartnerTxID(),
                'partnerGroupTxID'  => $this->getPartnerTxID(),
                'amount'            => $this->getAmount(),
                'currency'          => $this->getCurrency(),
                'merchantID'        => getenv('MOCA_MERCHANT_MERCHANT_ID'),
                'description'       => $this->getDescription(),
                'originTxID'        => $this->getOriginTxID(),
            );
            return MocaRestClient::post("/mocapay/partner/v2/refund", $requestBody, "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 6. getRefundStatus to check status end of transaction
    public function getRefundStatus() {
        try {
            $uri = 'mocapay/partner/v2/refund/'.$this->getPartnerTxID().'/status?currency='.$this->getCurrency() != ''? $this->getCurrency(): 'VND';

            return MocaRestClient::get($uri,'application/json', "ONLINE",$this->getAccessToken());
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // All api below for POS integration
    // 1. createQrCode to Create QR code for POS
    public function createQrCode() {
        try {
            $requestBody = array(
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'partnerTxID' => $this->getPartnerTxID()
            );
            $resp = MocaRestClient::post("/mocapay/partners/v1/terminal/qrcode/create", $requestBody, "OFFLINE");

            if ($resp->code == 200) {
                $this->setOriginTxID($resp->body->TxID);
                $this->setOrigPartnerTxID($this->getPartnerTxID());
            }

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 2. cancelTxn if user QR do not scan or expire
    public function cancelTxn() {
        try {
            $requestBody = array(
                'currency' => $this->getCurrency(),
                'origTxID' => $this->getOriginTxID(),
                'partnerTxID' => $this->getPartnerTxID()
            );

            return MocaRestClient::put("/mocapay/partners/v1/terminal/transaction/$this->getOriginTxID()/cancel", $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 3. refundPosTxn to refund transaction already success.
    public function refundPosTxn() {
        try {
            $requestBody = array(
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'origTxID' => $this->getOriginTxID(),
                'amount'   => $this->getAmount(),
                'partnerTxID' => $this->getPartnerTxID()
            );

            return MocaRestClient::put("/mocapay/partners/v1/terminal/transaction/$this->origPartnerTxID/refund", $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    // 4. performTxn use if method is CPQR
    public function performTxn() {
        try {
            $requestBody = array(
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency() != ''? $this->getCurrency(): 'VND',
                'partnerTxID' => $this->getPartnerTxID(),
                'code' => $this->getCode(),
            );

            return MocaRestClient::post("/mocapay/partners/v1/terminal/transaction/perform", $requestBody, "OFFLINE");
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
}
