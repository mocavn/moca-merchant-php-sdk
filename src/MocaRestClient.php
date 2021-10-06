<?php

namespace Moca\Merchant;

class MocaRestClient {

    public static function apiEndpoint() {
        if (getenv('MOCA_MERCHANT_ENVIRONMENT') == 'PRODUCTION') {
            if (getenv('MOCA_MERCHANT_COUNTRY') == 'VN') {
                return 'https://partner-gw.moca.vn';
            } else {
                return 'https://partner-api.grab.com';
            }
        } else {
            if (getenv('MOCA_MERCHANT_COUNTRY') == 'VN') {
                return 'https://stg-paysi.moca.vn';
            } else {
                return 'https://partner-api.stg-myteksi.com';
            }
        }
    }

    private static function now() {
        $now = new \DateTime('NOW');
        return $now->format(\DateTime::RFC7231);
    }

    private static function sha256($data) {
        return base64_encode(hash('sha256', $data, true));
    }

    private static function base64URLEncode($str) {
        return str_replace(['=', '+', '/'], ['', '-', '_'], ($str));
    }

    private static function generateHmac($requestMethod, $apiUrl, $contentType, $requestBody, $date) {
        $body = json_encode($requestBody);

        $hashedPayload = MocaRestClient::sha256($body);
        $content = '';
        $content .= $requestMethod;
        $content .= "\n";
        $content .= $contentType;
        $content .= "\n";
        $content .= $date;
        $content .= "\n";
        $content .= $apiUrl;
        $content .= "\n";
        $content .= $hashedPayload;
        $content .= "\n";

        return base64_encode(hash_hmac('sha256', $content, getenv('MOCA_MERCHANT_PARTNER_SECRET'), true));
    }

    private static function generatePOPSig($accessToken, $now) {
        $timestampUnix = $now->getTimestamp();
        $message = $timestampUnix . $accessToken;
        $utf8 = $message;
        $signature = base64_encode(hash_hmac('sha256', $utf8, getenv('MOCA_MERCHANT_CLIENT_SECRET'), true));
        $sub = MocaRestClient::base64URLEncode($signature);
        #echo $sub . PHP_EOL;

        $payload = [
            "time_since_epoch" => $timestampUnix,
            "sig" => $sub
        ];
        $payloadBytes = json_encode($payload);
        return MocaRestClient::base64URLEncode(base64_encode($payloadBytes));
    }

    private static function sendRequest($requestMethod, $apiUrl, $contentType, $requestBody, $type, $access_token) {
        $partnerID = getenv('MOCA_MERCHANT_PARTNER_ID');
        $grabID = getenv('MOCA_MERCHANT_GRAB_ID');
        $msgID = md5(uniqid(rand(), true));
        $timestamp = new \DateTime('NOW');
        $timestamp->modify('-7 hour');
        $now = $timestamp->format(\DateTime::RFC7231);
        $credentials = array();

        if ($type == "OFFLINE") {
            $terminalID = getenv('MOCA_MERCHANT_TERMINAL_ID');
            $credentials = array(
                'msgID' => $msgID,
                'grabID' => $grabID,
                'terminalID' => $terminalID
            );
        }
        if ($requestMethod == "GET") {
            if (strpos($apiUrl, '?') !== false) {
                $apiUrl .= '&';
            } else {
                $apiUrl .= '?';
            }
            $apiUrl .= urldecode(http_build_query($credentials));
        } else {
            $requestBody = array_merge($requestBody,$credentials);
        }

        $url = (MocaRestClient::apiEndpoint() . $apiUrl);

        $hmac = MocaRestClient::generateHmac($requestMethod, $apiUrl, $contentType, $requestBody, $now);
        if($apiUrl == '/mocapay/partner/v2/charge/complete' || $apiUrl == '/grabpay/partner/v2/charge/complete') {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
                'Date' => $now,
                'X-GID-AUX-POP' => MocaRestClient::generatePOPSig($access_token,$timestamp),
                'Authorization' => 'Bearer ' . $access_token
            );
        } else if($apiUrl == '/grabid/v1/oauth2/token') {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
            );
        } else if($type == "ONLINE" && $apiUrl !='/mocapay/partner/v2/charge/init' && $apiUrl !='/grabpay/partner/v2/charge/init') {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
                'Date' => $now,
                'Authorization' => 'Bearer ' . $access_token
            );
        } else {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
                'Date' => $now,
                'Authorization' => ($partnerID . ':' . $hmac)
            );
        }

        $response = null;

        $requestBody = \Unirest\Request\Body::json($requestBody);

        switch ($requestMethod) {
            case 'GET':
                $response = \Unirest\Request::get($url, $headers);
                break;

            case 'POST':
                $response = \Unirest\Request::post($url, $headers, $requestBody);
                break;

            case 'PUT':
                $response = \Unirest\Request::put($url, $headers, $requestBody);
                break;
        }
        return $response;
    }

    public static function get($apiUrl, $contentType, $type, $access_token='') {
        return self::sendRequest('GET', $apiUrl, $contentType, null, $type, $access_token);
    }

    public static function post($apiUrl, $requestBody, $type, $access_token='') {
        return self::sendRequest('POST', $apiUrl, 'application/json', $requestBody, $type, $access_token);
    }

    public static function put($apiUrl, $requestBody, $type, $access_token='') {
        return self::sendRequest('PUT', $apiUrl, 'application/json', $requestBody, $type, $access_token);
    }

}
