<?php

$client = new \GuzzleHttp\Client();

$baseUrl = SdkRestApi::getParam('base_url');
$endpoint = SdkRestApi::getParam('endpoint');
$url = rtrim($baseUrl) . '/' . ltrim($endpoint);
$method = SdkRestApi::getParam('method');
$body = SdkRestApi::getParam('body');
$pluginVersion = SdkRestApi::getParam('plugin_version');
$pluginName = SdkRestApi::getParam('plugin_name');

$res = $client->request(
    $method,
    $url,
    [
        'json' => $body,
        'headers' => [
            'Api-Token' => SdkRestApi::getParam('api_token'),
            'x-plugin-version' => $pluginVersion,
            'x-plugin-name' => $pluginName
        ]
    ]
);

/** @return array */
return json_decode($res->getBody(), true);
