<?php


namespace GNOffice\DirectCloud;

use GNOffice\DirectCloud\Exceptions\BadRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AccountInfoTokenProvider implements TokenProvider
{
    private string $service;
    private string $serviceKey;
    private string $code;
    private string $id;
    private string $password;
    private Client $client;

    public function __construct($service, $serviceKey, $code, $id, $password)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.directcloud.jp',
        ]);

        $this->service    = $service;
        $this->serviceKey = $serviceKey;
        $this->code       = $code;
        $this->id         = $id;
        $this->password   = $password;
    }

    public function getToken(): string
    {
        $json = [];
        $url  = '/openapi/jauth/token';

        $options = [
            'query'       => [
                'lang' => 'eng',
            ],
            'form_params' => [
                'service'     => $this->service,
                'service_key' => $this->serviceKey,
                'code'        => $this->code,
                'id'          => $this->id,
                'password'    => $this->password,
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
