<?php

namespace GNOffice\DirectCloud;

use GNOffice\DirectCloud\Exceptions\BadRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AccessKeyTokenProvider implements TokenProvider
{
    private string $service;
    private string $serviceKey;
    private string $accessKey;
    private Client $client;

    public function __construct($service, $serviceKey, $accessKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.directcloud.jp',
        ]);

        $this->service    = $service;
        $this->serviceKey = $serviceKey;
        $this->accessKey  = $accessKey;
    }

    public function getToken(): string
    {
        $json = [];
        $url  = '/openapi/jauth/access_token';

        $options = [
            'query'       => [
                'lang' => 'eng',
            ],
            'form_params' => [
                'service'     => $this->service,
                'service_key' => $this->serviceKey,
                'access_key'  => $this->accessKey,
            ]
        ];

        try {
            $response = $this->client->post($url, $options);

            $json = json_decode($response->getBody(), true);

            if ($json['success'] === false) {
                throw new BadRequest($response);
            }
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() !== 200) {
                throw new BadRequest($exception->getResponse());
            }
        }

        return $json['access_token'];
    }

}
