<?php

namespace Kenvel\Exceptions;

/**
 * Class PaymentDataException
 *
 * Ошибка данных платежа
 *
 * @package Kenvel\Exceptions
 */
class PaymentDataException extends \Exception
{
    protected $details;

    public function __construct($details = null)
    {
        $this->message = 'Payment data exception.';
        $this->details = $details;

        parent::__construct();
    }
}
