<?php

namespace sillsoft\BankId\src\response;

/**
 * Class ResourceOwnerResponse
 * @package sillsoft\BankId\src\response
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