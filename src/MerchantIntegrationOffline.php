<?php

namespace Moca\Merchant;

class MerchantIntegrationOffline
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

    public function __construct(string $environment, string $country, string $partnerID, string $partnerSecret, string $merchantID, string $terminalID)
    {
        $this->environment = $environment;
        $this->country = $country;
        $this->partnerID = $partnerID;
        $this->partnerSecret = $partnerSecret;
        $this->merchantID = $merchantID;
        $this->terminalID = $terminalID;
        $this->clientID = '';
        $this->clientSecret = '';
        $this->redirectUrl = '';
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
            $partnerInfo['posRefundTxn'] = "/mocapay/partners/v1/terminal/transaction/{origPartnerTxID}/refund";
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
            $partnerInfo['posRefundTxn'] = "/grabpay/partner/v1/terminal/transaction/{origPartnerTxID}/refund";
            $partnerInfo['performTxn'] = "/grabpay/partner/v1/terminal/transaction/perform";
            $partnerInfo['posChargeStatus'] = "/grabpay/partner/v1/terminal/transaction/{PartnerTxID}?currency={currency}&msgID={msgID}&txType=P2M";
            $partnerInfo['posChargeRefundStatus'] = "/grabpay/partner/v1/terminal/transaction/{refundPartnerTxID}?currency={currency}&msgID={msgID}&txType=Refund";
        }

        return $partnerInfo;
    } 

    /** 1. posCreateQRCode to Create QR code for POS
     * @param $msgID
     * @param $partnerTxID
     * @param $currency
     * @param $amount
     */
    public function posCreateQRCode($msgID, $partnerTxID, $amount, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $amount,
                'currency' => $currency,
                'partnerTxID' => $partnerTxID,
                'msgID' => $msgID
            );
            
            $resp = RestClient::post($env, $env['createQrCode'], $requestBody, "OFFLINE");

            return $resp;
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 2. posCancel if user QR do not scan or expire
     * @param $msgID
     * @param $partnerTxID
     * @param $currency
     * @param $originTxID
     */
    public function posCancel($msgID, $partnerTxID, $origPartnerTxID, $origTxID, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $currency,
                'origTxID' => $originTxID,
                'partnerTxID' => $partnerTxID,
                'msgID' => $msgID
            );
            
            return RestClient::put($env, str_replace("{origPartnerTxID}",$origPartnerTxID,$env['cancelQrTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 3. posRefund to refund transaction already success.
     * @param $msgID
     * @param $refundPartnerTxID
     * @param $currency
     * @param $origPartnerTxID
     * @param $amount
     * @param $description
     */
    public function posRefund($msgID, $refundPartnerTxID, $amount, $currency, $origPartnerTxID, $description) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'currency' => $currency,
                'amount'   => $amount,
                'reason'    => $description,
                'partnerTxID' => $refundPartnerTxID,
                'msgID' => $msgID
            );
            
            return RestClient::put($env, str_replace("{origPartnerTxID}",$originTxID,$env['posRefundTxn']), $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 4. posPerformQRCode use if method is CPQR
     * @param $msgID
     * @param $partnerTxID
     * @param $currency
     * @param $code
     * @param $amount
     */
    public function posPerformQRCode($msgID, $partnerTxID, $currency, $amount, $code) {
        try {
            $env = $this->getpartnerInfo();
            $requestBody = array(
                'amount' => $amount,
                'currency' => $currency,
                'partnerTxID' => $partnerTxID,
                'msgID' => $msgID,
                'code' => $code,
            );
            
            return RestClient::post($env, $env['performTxn'], $requestBody, "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 5. posGetTxnStatus: Get charge status use if method is CPQR
     * @param $msgID
     * @param $partnerTxID
     * @param $currency
     */
    public function posGetTxnStatus($msgID, $partnerTxID, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("{PartnerTxID}",$partnerTxID,$env['posChargeStatus']);
            $url = str_replace("{currency}",$currency,$url);
            $url = str_replace("{msgID}", $msgID,$url);
            
            return RestClient::get($env, $url, 'application/x-www-form-urlencoded', "OFFLINE");
        } catch (Exception $e) {
            return 'Caught exception: ' . $e->getMessage() . "\n";
        }
    }

    /** 6. posGetRefundStatus: Get charge status use if method is CPQR
     * @param $msgID
     * @param $refundPartnerTxID
     * @param $currency
     */
    public function posGetRefundStatus($msgID, $refundPartnerTxID, $currency) {
        try {
            $env = $this->getpartnerInfo();
            $url = str_replace("{refundPartnerTxID}",$refundPartnerTxID,$env['posChargeRefundStatus']);
            $url = str_replace("{currency}",$currency,$url);
            $url = str_replace("{msgID}", $msgID);
            
            return RestClient::get($env, $url, 'application/x-www-form-urlencoded', "OFFLINE");
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
