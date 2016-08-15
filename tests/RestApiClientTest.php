<?php

use Codeages\RestApiClient\RestApiClient;
use Codeages\RestApiClient\Specification\JsonHmacSpecification;
use Codeages\RestApiClient\HttpRequest\MockHttpRequest;
use Codeages\RestApiClient\Tests\TestLogger;

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
        $logger = new TestLogger();

        $client = new RestApiClient($config, $spec, $http, $logger, true);

        $http->mock(function() {
            return 1;
        });
        $result = $client->get('/');

        $this->assertEquals(1, $result);
    }

}
