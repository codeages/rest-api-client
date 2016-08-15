# REST API Client

## Install

```
composer require codeages/rest-api-client
```

## Useage

```
$config = array(
    'accessKey' => 'testkey',
    'secretKey' => 'secretKey',
    'endpoint' => 'http://domain.tld/api/v1/',
);

$spec = new JsonHmacSpecification();

$client = new RestApiClient($config, $spec);

$result = $client->get('/');
```