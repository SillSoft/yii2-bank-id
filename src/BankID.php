<?php

namespace sillsoft\BankId;

use sillsoft\BankId\exceptions\BadResponseException;
use sillsoft\BankId\response\AccessTokenResponse;
use sillsoft\BankId\response\ResourceClientResponse;
use sillsoft\BankId\valueObject\AccessToken;
use yii\httpclient\Client;
use yii\httpclient\Response;

/**
 * Class BankID
 * @package sillsoft\BankId
 */
class BankID
{
    /**
     * @var string
     */
    protected string $host;

    /**
     * @var string
     */
    protected string $clientId;

    /**
     * @var string
     */
    protected string $clientSecret;

    /**
     * @var string
     */
    protected string $cert;

    /**
     * @var string
     */
    protected string $apiVer = 'v1';

    /**
     * @var string|null
     */
    protected ?string $state;

    /**
     * @param string $host
     * @param string $clientId
     * @param string $clientSecret
     * @param string $cert
     */
    public function __construct(string $host, string $clientId, string $clientSecret, string $cert)
    {
        $this->host = $host;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cert = $cert;
    }

    /**
     * @param int $dataset
     * @return string
     * @throws BadResponseException
     * @throws \Random\RandomException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAuthorizationUrl(int $dataset): string
    {
        $state = $this->getRandomState();
        $this->state = $state;
        $body = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'state' => $state,
            'dataset' => $dataset,
        ];
        $httpResponse = $this->sendRequest($this->getApiUrl() . '/bank/oauth2/authorize', 'GET', $body);
        if ($httpResponse->getStatusCode() != 200)
            throw new BadResponseException($httpResponse);

        return $this->host . $httpResponse->headers->get('location');
    }

    /**
     * @param string $code
     * @return AccessToken
     * @throws BadResponseException
     * @throws \Random\RandomException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAccessToken(string $code): AccessToken
    {
        $state = $this->getRandomState();
        $this->state = $state;
        $body = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];
        $httpResponse = $this->sendRequest($this->getApiUrl() . '/bank/oauth2/token', 'POST', $body);
        if ($httpResponse->getStatusCode() != 200)
            throw new BadResponseException($httpResponse);

        $prepareResponse = new AccessTokenResponse($httpResponse->getData());
        return new AccessToken(['accessToken' => $prepareResponse->getAccessToken()]);
    }

    /**
     * @param AccessToken $accessToken
     * @return void
     * @throws BadResponseException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getResourceOwner(AccessToken $accessToken)
    {
        $response = $this->fetchResourceOwnerDetails($accessToken);
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken->accessToken,
        ];
        $body = [
            'cert' => $response->getCert(),
            'sidBi' => $response->getSidBi(),
            'memberId' => $response->getMemberId(),
            'fields' => [
                'lastName',
                'firstName',
                'middleName',
                'phone',
                'inn',
                'birthDay',
                'birthPlace',
                'sex',
                'email'
            ],
            'addresses' => [
                'type' => 'factual',
                'fields' => [
                    'country',
                    'state',
                    'area',
                    'city',
                    'street',
                    'houseNo',
                    'flatNo'
                ]
            ],
            'documents' => [
                'type' => 'passport',
                'fields' => [
                    'typeName',
                    'series',
                    'number',
                    'issue',
                    'dateIssue',
                    'dateExpiration'
                ]
            ]
        ];
        $httpResponse = $this->sendRequest($this->getApiUrl() . '/bank/data', 'POST', $body, $headers);
        if ($httpResponse->getStatusCode() != 200)
            throw new BadResponseException($httpResponse);

        var_dump($httpResponse->getData());
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @return string
     */
    protected function getCert(): string
    {
        return $this->cert;
    }

    /**
     * @param AccessToken $accessToken
     * @return ResourceClientResponse
     * @throws BadResponseException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    protected function fetchResourceOwnerDetails(AccessToken $accessToken): ResourceClientResponse
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken->accessToken,
        ];
        $body = [
            'cert' => base64_encode($this->getCert()),
        ];
        $httpResponse = $this->sendRequest($this->getApiUrl() . '/bank/resource/client', 'POST', $body, $headers);
        if ($httpResponse->getStatusCode() != 200)
            throw new BadResponseException($httpResponse);

        return new ResourceClientResponse($httpResponse->getData());
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    protected function sendRequest(string $url, string $method, array $data, array $headers = []): Response
    {
        $client = new Client();
        return $client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setData($data)
            ->addHeaders($headers)
            ->send();
    }

    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return $this->host . '/' . $this->apiVer;
    }

    /**
     * @param int $length
     * @return string
     * @throws \Random\RandomException
     */
    protected function getRandomState(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}