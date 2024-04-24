<?php

namespace sillsoft\BankId\response;

/**
 * Class ResourceOwnerResponse
 * @package sillsoft\BankId\response
 */
class ResourceOwnerResponse
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
}