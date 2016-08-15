<?php
namespace Codeages\RESTAPIClient\Specification;

class JsonHmacSpecification implements Specification
{
    public function getHeaders($token, $requestId = '')
    {
        $headers = array();
        $headers[] = 'Content-type: application/json';
        $headers[] = "Auth-Token: {$token}";
        $headers[] = "API-REQUEST-ID: {$requestId}";

        return $headers;
    }

    public function packToken($config, $url, $body, $deadline, $once)
    {
        $signature = $this->signature($config, $url, $body, $deadline, $once);
        return "{$config['accessKey']}:{$deadline}:{$once}:{$signature}";
    }

    public function unpackToken($string)
    {
        $token = explode(':', $string);
        if (count($token) !== 4) {
            throw new \InvalidArgumentException('token invalid.');
        }

        return array (
            'accessKey' => $token[0],
            'deadline' => $token[1],
            'once' => $token[2],
            'signature' => $token[3],
        );
    }

    public function signature($config, $url, $body, $deadline, $once)
    {
        $data = implode("\n", [$url, $deadline, $once, $body]);
        $signature = hash_hmac('sha1', $data, $config['secretKey'], true);
        $signature = str_replace(array('+', '/'), array('-', '_'), base64_encode($signature));
        return $signature;
    }

    public function serialize($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException("In json hmac specification serialize data must be array.");
        }

        $json = json_encode($data);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'json_encode error: ' . json_last_error_msg());
        }

        return $json;
    }

    public function unserialize($data)
    {
        $data = json_decode($data, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                'json_decode error: ' . json_last_error_msg());
        }

        return $data;
    }
}