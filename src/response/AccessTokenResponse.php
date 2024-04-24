<?php

namespace sillsoft\BankId\response;

/**
 * Class AccessTokenResponse
 * @package sillsoft\BankId\response
 */
class AccessTokenResponse
{
    /**
     * @var array
     */
    protected array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }


    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getAccessToken(): ?string
    {
        return array_key_exists('access_token', $this->getData()) ? $this->getData()['access_token'] : null;
    }
}