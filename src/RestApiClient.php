<?php
namespace Codeages\RestApiClient;

use Psr\Log\LoggerInterface;
use Codeages\RestApiClient\Specification\Specification;
use Codeages\RestApiClient\Exceptions\ServerException;
use Codeages\RestApiClient\Exceptions\ResponseException;

class RestApiClient
{
    protected $config;

    protected $debug;

    protected $logger;

    protected $http;

    public function __construct($config, Specification $spec, $debug = false, LoggerInterface $logger = null)
    {
        $this->config = array_merge(array(
            'lifetime' => 600,
        ), $config);

        $this->spec = $spec;
        $this->debug = $debug;
        $this->logger = $logger;
        $this->http = array(
            'userAgent' => 'Codeages Rest Api Client v1.0.0',
            'connectTimeout' => isset($config['connectTimeout']) ? intval($config['connectTimeout']) : 10,
            'timeout' => isset($config['timeout']) ? intval($config['timeout']) : 10,
        );
    }

    public function post($uri, array $params = array(), array $header = array())
    {
        return $this->_request('POST', $uri, $params, $header);
    }

    public function put($uri, array $params = array(), array $header = array())
    {
        return $this->_request('PUT', $uri, $params, $header);
    }

    public function patch($uri, array $params = array(), array $header = array())
    {
        return $this->_request('PATCH', $uri, $params, $header);
    }

    public function get($uri, array $params = array(), array $header = array())
    {
        return $this->_request('GET', $uri, $params, $header);
    }

    public function delete($uri, array $params = array(), array $header = array())
    {
        return $this->_request('DELETE', $uri, $params, $header);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    protected function _request($method, $uri, $params, $headers)
    {
        $requestId = $this->makeRequestId();

        $url = $this->makeUrl($uri);

        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] {$method} {$url}", array('params' => $params, 'headers' => $headers));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, $this->http['userAgent']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->http['connectTimeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->http['timeout']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

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

        $body = ($method == 'GET') ? '' : $this->spec->serialize($params);
        if ($body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $token = $this->spec->packToken($this->config, $uri, $body, time() + $this->config['lifetime'], $requestId);

        $headers = $this->spec->getHeaders($token, $requestId);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

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

    protected function makeRequestId()
    {
        return ((string) (microtime(true) * 10000)) . substr(md5(uniqid('', true)), -18);
    }

    protected function makeUrl($uri)
    {
        return rtrim($this->config['endpoint'], "\/") . $uri;
    }

}