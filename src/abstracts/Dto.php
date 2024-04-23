<?php

namespace sillsoft\BankId\src\abstracts;

use yii\base\BaseObject;

/**
 * Class Dto
 * @package sillsoft\BankId\src\abstracts
 */
abstract class Dto extends BaseObject
{

    /**
     * @param array $data
     * @param bool $safe
     */
    public function load(array $data, bool $safe = true): void
    {
        foreach ($data as $key => $value) {

            if (!$safe || $this->canSetProperty($key)) {

                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function __toArray(): array
    {
        return (array)$this;
    }
}