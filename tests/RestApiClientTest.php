<?php

use Codeages\RestApiClient\RestApiClient;
use Codeages\RestApiClient\Specification\JsonHmacSpecification;

class RestApiClientTest extends \PHPUnit_Framework_TestCase
{

    public function testSendAuthCode()
    {
        $config = array(
            'accessKey' => 'testkey',
            'secretKey' => 'secretKey',
            'endpoint' => 'http://domain.tld/api/v1/',
        );
        $spec = new JsonHmacSpecification();

        $client = new RestApiClient($config, $spec);

        $result = $client->get('/');

        var_dump($result);


        // $service = new ApiService([
        //     'accessKey' => 'N3fSA9br6d4Ko9fbwuhZNmjkxElgEAlr',
        //     'secretKey' => 'DAkdKwFQJKB8TQjqlAp8XJtuoOL3p6u2',
        //     'endpoint' => 'root-api.dev.com/v1'
        // ]);

        // $me = $service->getMe();

        // var_dump($me);
    }

}
