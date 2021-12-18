<?php

namespace Moca\Merchant;

class RestClient {

    public static function sha256($data) {
        return base64_encode(hash('sha256', $data, true));
    }

    public static function base64URLEncode($str) {
        return str_replace(['=', '+', '/'], ['', '-', '_'], ($str));
    }

    public static function generateHmac($env,$requestMethod, $apiUrl, $contentType, $requestBody, $date) {
        if($requestBody != "") {
            $body = json_encode($requestBody);
            $hashedPayload = RestClient::sha256($body);
        } else {
            $hashedPayload = '';
        }
        
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

    public static function generatePOPSig($env,$accessToken, $timestampUnix) {
        
        $message = $timestampUnix . $accessToken;
        $utf8 = $message;
        $signature = base64_encode(hash_hmac('sha256', $utf8, $env['clientSecret'], true));
        $sub = RestClient::base64URLEncode($signature);
        #echo $sub . PHP_EOL;

        $payload = [
            "time_since_epoch" => $timestampUnix,
            "sig" => $sub
        ];
        $payloadBytes = json_encode($payload);
        return RestClient::base64URLEncode(base64_encode($payloadBytes));
    }

    public static function sendRequest($env, $requestMethod, $apiUrl, $contentType, $requestBody, $type, $access_token) {
        $now = new \DateTime('NOW');
        $timestampUnix = $now->getTimestamp();
        $gmtTime = $now->format(\DateTime::RFC7231);
        $credentials = array();
        // merge body when call offline API
        if ($type == "OFFLINE") {
            $credentials = array(
                'grabID' => $env['merchantID'],
                'terminalID' => $env['terminalID']
            );
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
        }

        $url = ($env['url'] . $apiUrl);
        $headers = array(
            'Accept' => 'application/json',
            'X-Request-ID' => md5(uniqid(rand(), true)),
            'Content-Type' => $contentType,
            'X-Sdk-Country' => $env['country'],
            'X-Sdk-Version' => $env['sdkVersion'],
        );

        $hmac = RestClient::generateHmac($env, $requestMethod, $apiUrl, $contentType, $requestBody, $gmtTime);
        // set header for API OAuth token 
        if( $apiUrl == $env['OAuth2Token']) {
            $headers = $headers;
        }
        // set header for online API and not for charge init and OTC status
        else if($type == "ONLINE" && $apiUrl != $env['chargeInit'] && !str_contains($apiUrl,'one-time-charge') ) {
            $headers = array_merge($headers, array(
                'Date' => $gmtTime,
                'X-GID-AUX-POP' => RestClient::generatePOPSig($env, $access_token,$timestampUnix),
                'Authorization' => 'Bearer ' . $access_token,
            ));
        // default
        } else {
            $headers = array_merge($headers, array(
                'X-Request-ID' => $msgID,
                'Date' => $gmtTime,
                'Authorization' => ($env['partnerID'] . ':' . $hmac),
            ));
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
