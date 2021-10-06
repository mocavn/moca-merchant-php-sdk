<?php

namespace Moca\Merchant;

class MocaRestClient {

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

    private static function generateHmac($env,$requestMethod, $apiUrl, $contentType, $requestBody, $date) {
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

        return base64_encode(hash_hmac('sha256', $content, $env['partnerSecret'], true));
    }

    private static function generatePOPSig($env,$accessToken, $now) {
        $timestampUnix = $now->getTimestamp();
        $message = $timestampUnix . $accessToken;
        $utf8 = $message;
        $signature = base64_encode(hash_hmac('sha256', $utf8, $env['clientSecret'], true));
        $sub = MocaRestClient::base64URLEncode($signature);
        #echo $sub . PHP_EOL;

        $payload = [
            "time_since_epoch" => $timestampUnix,
            "sig" => $sub
        ];
        $payloadBytes = json_encode($payload);
        return MocaRestClient::base64URLEncode(base64_encode($payloadBytes));
    }

    private static function sendRequest($env, $requestMethod, $apiUrl, $contentType, $requestBody, $type, $access_token) {
        $partnerID = $env['partnerID'];
        $grabID = $env['merchantID'];
        $msgID = md5(uniqid(rand(), true));
        $timestamp = new \DateTime('NOW');
        $now = $timestamp->format(\DateTime::RFC7231);
        $credentials = array();
        // merge body when call offline API
        if ($type == "OFFLINE") {
            $terminalID = $env['terminalID'];
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

        $url = ($env['url'] . $apiUrl);

        $hmac = MocaRestClient::generateHmac($env, $requestMethod, $apiUrl, $contentType, $requestBody, $now);
        // set header for API charge complete 
        if($apiUrl == $env['chargeComplete']) {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
                'Date' => $now,
                'X-GID-AUX-POP' => MocaRestClient::generatePOPSig($env, $access_token,$timestamp),
                'Authorization' => 'Bearer ' . $access_token
            );
        // set header for API oauth token    
        } else if($apiUrl == $env['OAuth2Token']) {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
            );
        // set header for api charge init
        } else if($type == "ONLINE" && $apiUrl != $env['chargeInit']) {
            $headers = array(
                'Accept' => 'application/json',
                'Content-Type' => $contentType,
                'Date' => $now,
                'Authorization' => 'Bearer ' . $access_token
            );
        // default header
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

    public static function get($env, $apiUrl, $contentType, $type, $access_token='') {
        return self::sendRequest($env, 'GET', $apiUrl, $contentType, null, $type, $access_token);
    }

    public static function post($env, $apiUrl, $requestBody, $type, $access_token='') {
        return self::sendRequest($env, 'POST', $apiUrl, 'application/json', $requestBody, $type, $access_token);
    }

    public static function put($env, $apiUrl, $requestBody, $type, $access_token='') {
        return self::sendRequest($env, 'PUT', $apiUrl, 'application/json', $requestBody, $type, $access_token);
    }

}
