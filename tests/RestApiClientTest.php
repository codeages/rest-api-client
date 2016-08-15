<?php

use Codeages\RestApiClient\RestApiClient;
use Codeages\RestApiClient\Specification\JsonHmacSpecification;
use Codeages\RestApiClient\HttpRequest\MockHttpRequest;

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
        $http = new MockHttpRequest([]);

        $client = new RestApiClient($config, $spec, $http);

        $http->mock(function() {
            return 1;
        });
        $result = $client->get('/');

        $this->assertEquals(1, $result);
    }

}
