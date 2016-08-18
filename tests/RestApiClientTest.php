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
            'accessKey' => 'test_acess_key',
            'secretKey' => 'test_secret_key',
            'endpoint' => 'http://passport.dev.com/api/v1',
        );
        $spec = new JsonHmacSpecification();
        $logger = new TestLogger();

        $client = new RestApiClient($config, $spec, null, $logger, true);

        $result = $client->get('/users/1');

        var_dump($result);

        // $this->assertEquals(1, $result);
    }

}
