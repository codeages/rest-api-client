<?php

use Codeages\RESTAPIClient\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    public function testSendAuthCode()
    {
        $client = new Client([]);

        var_dump($client);


        // $service = new ApiService([
        //     'accessKey' => 'N3fSA9br6d4Ko9fbwuhZNmjkxElgEAlr',
        //     'secretKey' => 'DAkdKwFQJKB8TQjqlAp8XJtuoOL3p6u2',
        //     'endpoint' => 'root-api.dev.com/v1'
        // ]);

        // $me = $service->getMe();

        // var_dump($me);
    }

}
