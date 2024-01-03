<?php

$client = new \GuzzleHttp\Client();

$baseUrl = SdkRestApi::getParam('base_url');
$endpoint = SdkRestApi::getParam('endpoint');
$url = rtrim($baseUrl) . '/' . ltrim($endpoint);
$method = SdkRestApi::getParam('method');
$body = SdkRestApi::getParam('body');

$res = $client->request(
    $method,
    $url,
    [
        'json' => $body,
        'headers' => [
            'Api-Token' => SdkRestApi::getParam('api_token')
        ]
    ]
);

/** @return array */
return json_decode($res->getBody(), true);
