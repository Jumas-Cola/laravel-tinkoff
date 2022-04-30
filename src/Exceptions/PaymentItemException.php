<?php

namespace Kenvel\Exceptions;

/**
 * Class PaymentItemException
 *
 * Ошибка товаров платежа
 *
 * @package Kenvel\Exceptions
 */
class PaymentItemException extends \Exception
{
    protected $details;

    public function __construct($details = null)
    {
        $this->message = 'Payment item exception.';
        $this->details = $details;

        parent::__construct();
    }
}
