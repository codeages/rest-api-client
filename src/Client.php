<?php
namespace Codeages\RESTAPIClient;

use Codeages\RESTAPIClient\Exceptions\ServerException;
use Codeages\RESTAPIClient\Exceptions\ResponseException;

use Psr\Log\LoggerInterface;

class Client
{
    protected $config;

    protected $debug;

    protected $logger = null;

    public function __construct($config, $spec);
    {
        $this->config = $config;
        $this->debug = empty($config['debug']) ? false : true;
        $this->spec = $spec;
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
        $requestId = substr(md5(uniqid('', true)), -16);

        $url = $this->_makeUrl($uri);

        $this->debug && $this->logger && $this->logger->debug("[{$requestId}] {$method} {$url}", array('params' => $params, 'headers' => $headers));

        $curl = curl_init();

        // curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            if (!empty($params)) {
                $url = $url.(strpos($url, '?') ? '&' : '?').http_build_query($params);
            }
        }

        $body = ($method == 'GET') ? '' : $this->spec->serialize($params);
        $vector = $this->spec->sign($this->config, $url, $body, time() + $this->config['deadline'], $requestId);

        $vector->headers[] = "API-REQUEST-ID: {$requestId}";

        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($vector->headers, $headers);
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

    protected function _makeUrl($uri)
    {
        return 'http://' . $this->config['endpoint'] . $uri;
    }

}