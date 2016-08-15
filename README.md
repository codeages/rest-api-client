# REST API Client

## Install

```
composer require codeages/rest-api-client
```

## Useage

```php
$config = array(
    'accessKey' => 'testkey',
    'secretKey' => 'secretKey',
    'endpoint' => 'http://domain.tld/api/v1/',
);

$spec = new JsonHmacSpecification('sha1');

$client = new RestApiClient($config, $spec);

$result = $client->get('/');
```