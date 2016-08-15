<?php
namespace Codeages\RestApiClient\HttpRequest;

use Codeages\RestApiClient\Exceptions\ServerException;
use Codeages\RestApiClient\Exceptions\ResponseException;

class CurlHttpRequest extends HttpRequest
{
    public function request($method, $url, $body, array $headers = array())
    {
        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] {$method} {$url}", array('params' => $params, 'headers' => $headers));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, $this->options['userAgent']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->options['connectTimeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->options['timeout']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } else {
            if (!empty($params)) {
                $url = $url.(strpos($url, '?') ? '&' : '?').http_build_query($params);
            }
        }

        if ($body && $method != 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $curlinfo = curl_getinfo($curl);

        $header = substr($response, 0, $curlinfo['header_size']);
        $body   = substr($response, $curlinfo['header_size']);

        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] CURL_INFO", $curlinfo);
        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] RESPONSE_HEADER {$header}");
        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] RESPONSE_BODY {$body}");

        curl_close($curl);

        $context = array(
            'CURLINFO' => $curlinfo,
            'HEADER'   => $header,
            'BODY'     => $body
        );

        if (empty($curlinfo['namelookup_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] NAME_LOOK_UP_TIMEOUT", $context);
        }

        if (empty($curlinfo['connect_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] API_CONNECT_TIMEOUT", $context);
            throw new ResponseException("Connect api server timeout (url: {$url}).");
        }

        if (empty($curlinfo['starttransfer_time'])) {
            $this->logger && $this->logger->error("[{$requestId}] API_TIMEOUT", $context);
            throw new ResponseException("Request api server timeout (url:{$url}).");
        }

        if ($curlinfo['http_code'] >= 500) {
            $this->logger && $this->logger->error("[{$requestId}] API_RESOPNSE_ERROR", $context);
            throw new ServerException("Api server internal error (url:{$url}).");
        }

        $result = $this->spec->unserialize($body);

        if (empty($result)) {
            $this->logger && $this->logger->error("[{$requestId}] RESPONSE_JSON_DECODE_ERROR", $context);
            throw new ResponseException("Api result json decode error: (url:{$url}).");
        }

        return $result;
    }

}