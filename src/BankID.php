<?php

namespace sillsoft\BankId\src;

use sillsoft\BankId\src\exceptions\BadResponseException;
use sillsoft\BankId\src\response\AccessTokenResponse;
use sillsoft\BankId\src\response\ResourceClientResponse;
use sillsoft\BankId\src\valueObject\AccessToken;
use yii\base\InvalidArgumentException;
use yii\httpclient\Client;
use yii\httpclient\Response;

/**
 * Class BankID
 * @package sillsoft\BankId\src
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

        $this->validateCert();
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
        if ($httpResponse->getStatusCode() == 200)
            throw new BadResponseException($httpResponse);

        return $this->getApiUrl() . '/' . $httpResponse->headers->get('location');
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
        if ($httpResponse->getStatusCode() == 200)
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
        ];
        $httpResponse = $this->sendRequest($this->getApiUrl() . '/bank/data', 'POST', $body, $headers);
        if ($httpResponse->getStatusCode() == 200)
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
        if ($httpResponse->getStatusCode() == 200)
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

    /**
     * @return void
     */
    protected function validateCert(): void
    {
        if (!file_exists($this->cert))
            throw new InvalidArgumentException('Certificate file does not exist');
    }

}