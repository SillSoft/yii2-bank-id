<?php

namespace sillsoft\BankId\src\response;

/**
 * Class ResourceClientResponse
 * @package sillsoft\BankId\src\response
 */
class ResourceClientResponse
{
    /**
     * @var array
     */
    protected array $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getCert(): string
    {
        return $this->response['cert'];
    }

    /**
     * @return string
     */
    public function getCustomerCrypto(): string
    {
        return $this->response['customerCrypto'];
    }

    /**
     * @return string
     */
    public function getSidBi(): string
    {
        return $this->response['sidBi'];
    }

    /**
     * @return string
     */
    public function getMemberId(): string
    {
        return (string)$this->response['memberId'];
    }

}