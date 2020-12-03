<?php

namespace Moca\Merchant;

class MocaRestClient {

    public static function apiEndpoint() {
		if (getenv('MOCA_MERCHANT_ENVIRONMENT') == 'PRODUCTION') {
			return 'https://partner-gw.moca.vn';
		} else {
			return 'https://stg-paysi.moca.vn';
		}
	}

	private static function now() {
		$now = new \DateTime('NOW');
		return $now->format(\DateTime::RFC7231);
	}

	private static function sha256($data) {
		return base64_encode(hash('sha256', $data, true));
	}

	private static function generateHmac($requestMethod, $apiUrl, $contentType, $requestBody, $date) {
		$body = json_encode($requestBody);
		$content = '';
		$content .= $requestMethod;
		$content .= '\n';
		$content .= $contentType;
		$content .= '\n';
		$content .= $date;
		$content .= '\n';
		$content .= $apiUrl;
		$content .= '\n';
		$content .= strlen($body) > 0 ? self::sha256($body) : '';
		$content .= '\n';

		return base64_encode(hash_hmac('sha256', $content, getenv('MOCA_MERCHANT_PARTNER_SECRET'), true));
	}

	private static function sendRequest($requestMethod, $apiUrl, $contentType, $requestBody) {
        $partnerID = getenv('MOCA_MERCHANT_PARTNER_ID');
        $grabID = getenv('MOCA_MERCHANT_GRAB_ID');
        $msgID = md5(uniqid(rand(), true));
        
        $now = self::now();
        $credentials = array();

        if (getenv('MOCA_MERCHANT_TYPE') == "OFFLINE") {
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
            $requestBody = array_merge($requestBody, $credentials);
        }

        $url = (self::apiEndpoint() . $apiUrl);

        $hmac = self::generateHmac($requestMethod, $url, $contentType, $requestBody, $now);
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => $contentType,
            'Date' => $now,
            'Authorization' => ($partnerID . ':' . $hmac)
        );
        $response = null;

        $requestBody = \Unirest\Request\Body::json($requestBody);

        switch ($requestMethod) {
            case 'GET':
                $response = \Unirest\Request::get($url, $headers);
                break;

            case 'POST':
                $response = \Unirest\Request::post($url, $headers, serialize($requestBody));
                break;

            case 'PUT':
                $response = \Unirest\Request::put($url, $headers, serialize($requestBody));
                break;
        }
        return $response;
	}

	public static function get($apiUrl, $contentType) {
		return self::sendRequest('GET', $apiUrl, $contentType, null);
	}

	public static function post($apiUrl, $requestBody) {
		return self::sendRequest('POST', $apiUrl, 'application/json', $requestBody);
	}

	public static function put($apiUrl, $requestBody) {
		return self::sendRequest('PUT', $apiUrl, 'application/json', $requestBody);
	}

}