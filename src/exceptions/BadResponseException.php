<?php

namespace sillsoft\BankId\exceptions;

use Throwable;
use yii\httpclient\Response;

/**
 * Class BadResponseException
 * @package sillsoft\BankId\exceptions
 */
class BadResponseException extends \Exception
{
    /**
     * @const integer
     */
    private const MAX_BODY_LENGTH = 1024;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Response $response
     * @param Throwable|null $previous
     * @throws \yii\httpclient\Exception
     */
    public function __construct(Response $response, Throwable $previous = null)
    {
        $this->response = $response;

        $message = json_encode(
            [
                'statusCode' => $response->getStatusCode(),
                'body' => mb_substr($response->getContent(), 0, self::MAX_BODY_LENGTH),
            ]
        );

        parent::__construct($message, 0, $previous);
    }
}