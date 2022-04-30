<?php

/**
 * Tinkoff bank acquiring
 *
 * Simple Tinkoff bank acquiring library
 * Based on https://oplata.tinkoff.ru/landing/develop/documentation
 *
 * @link   https://github.com/jumas-cola/laravel-tinkoff
 * @version 1.0
 * @author Dmitry Kenvel <dimult@yahoo.com>
 */

namespace Kenvel;

use Illuminate\Support\Facades\Http;
use Kenvel\Exceptions\HttpException;
use Kenvel\Exceptions\PaymentDataException;
use Kenvel\Exceptions\PaymentItemException;

class LaravelTinkoffClass
{
    private $acquiring_url;
    private $terminal_id;
    private $secret_key;

    private $url_init;
    private $url_cancel;
    private $url_confirm;
    private $url_get_state;

    protected $response;

    protected $payment_id;
    protected $payment_url;
    protected $payment_status;

    protected const OPTIONAL_FIELDS = [
        'IP',
        'Description',
        'Currency',
        'PayType',
        'Language',
        'NotificationURL',
        'SuccessURL',
        'FailURL',
        'RedirectDueDate',
        'Shops',
        'Receipts',
        'Descriptor',
    ];

    /**
     * Inicialize LaravelTinkoffClass class
     *
     */
    public function __construct()
    {
        $this->acquiring_url = config('tinkoff.acquiring_url');
        $this->terminal_id = config('tinkoff.terminal_id');
        $this->secret_key = config('tinkoff.secret_key');
        $this->setupUrls();
    }

    /**
     * Generate payment URL
     *
     * -------------------------------------------------
     * For generate url need to send $payment array and array of $items
     * All keys for correct checking in paymentArrayChecked()
     * and itemsArrayChecked()
     *
     * Tinkoff does not accept a Item name longer than $item_name_max_lenght
     * $amount_multiplicator - need for convert price to cents
     *
     * @param  array  $payment array of payment data
     * @param  array  $items   array of items
     * @return mixed - return payment url if has no errors
     */
    public function paymentURL(array $payment, array $items)
    {
        if (!$this->paymentArrayChecked($payment)) {
            throw new PaymentDataException('Incomplete payment data.');
        }

        $item_name_max_lenght = 64;
        $amount_multiplicator = 100;

        /**
         * Generate items array for Receipt
         */
        foreach ($items as $item) {
            if (!$this->itemsArrayChecked($item)) {
                throw new PaymentItemException('Incomplete items data.');
            }

            $payment['Items'][] = [
                'Name' => mb_strimwidth(
                    $item['Name'],
                    0,
                    $item_name_max_lenght - 1,
                    ''
                ),
                'Price' => round($item['Price'] * $amount_multiplicator),
                'Quantity' => $item['Quantity'],
                'Amount' => round(
                    $item['Price'] * $item['Quantity'] * $amount_multiplicator
                ),
                'Tax' => $item['NDS'],
            ];
        }

        $params = [
            'OrderId' => $payment['OrderId'],
            'Amount' => round($payment['Amount'] * $amount_multiplicator),
            'DATA' => [
                'Email' => $payment['Email'],
                'Phone' => $payment['Phone'],
                'Name' => $payment['Name'],
            ],
            'Receipt' => [
                'Email' => $payment['Email'],
                'Phone' => $payment['Phone'],
                'Taxation' => $payment['Taxation'],
                'Items' => $payment['Items'],
            ],
        ];

        foreach (self::OPTIONAL_FIELDS as $field) {
            if (
                array_key_exists($field, $payment) and !empty($payment[$field])
            ) {
                $params[$field] = $payment[$field];
            }
        }

        $this->sendRequest($this->url_init, $params);

        return $this->payment_url;
    }

    /**
     * Check payment status
     *
     * @param  [string] Tinkoff payment id
     * @return [mixed] status of payment or false
     */
    public function getState($payment_id)
    {
        $params = ['PaymentId' => $payment_id];

        $this->sendRequest($this->url_get_state, $params);

        return $this->payment_status;
    }

    /**
     * Confirm payment
     *
     * @param  [string] Tinkoff payment id
     * @return [mixed] status of payment or false
     */
    public function confirmPayment($payment_id)
    {
        $params = ['PaymentId' => $payment_id];

        $this->sendRequest($this->url_confirm, $params);

        return $this->payment_status;
    }

    /**
     * Cancel payment
     *
     * @param  [string] Tinkoff payment id
     * @return [mixed] status of payment or false
     */
    public function cancelPayment($payment_id)
    {
        $params = ['PaymentId' => $payment_id];

        $this->sendRequest($this->url_cancel, $params);

        return $this->payment_status;
    }

    /**
     * Validate notification request
     *
     * @param  [array] Tinkoff notification request data
     * @return boolean
     */
    public function checkNotification($request_data)
    {
        $args_token = $request_data['Token'];

        return isset($args_token) and
            $args_token == self::generateToken($request_data);
    }

    /**
     * Send reques to bank acquiring API
     *
     * @param  [string] $path url
     * @param  [array]  $args data
     * @return [json]   json decoded data
     */
    private function sendRequest($path, array $args)
    {
        $args['TerminalKey'] = $this->terminal_id;
        $args['Token'] = $this->generateToken($args);

        $response = Http::retry(5, 100)->post($path, $args);

        $json = json_decode($response->body());

        $this->payment_id = @$json->PaymentId;
        $this->payment_url = @$json->PaymentURL;
        $this->payment_status = @$json->Status;

        return true;
    }

    /**
     * Generate sha256 token for bank API
     *
     * @param  array of args
     * @return sha256 token
     */
    private function generateToken(array $args)
    {
        $token = '';
        $args['Password'] = $this->secret_key;
        $args['TerminalKey'] = $this->terminal_id;
        ksort($args);

        foreach ($args as $key => $arg) {
            if (!is_array($arg) && $key != 'Token') {
                if (is_bool($arg)) {
                    $token .= $arg ? 'true' : 'false';
                } else {
                    $token .= $arg;
                }
            }
        }

        return hash('sha256', $token);
    }

    /**
     * Setting up urls for API
     *
     * @return void
     */
    private function setupUrls()
    {
        $this->acquiring_url = $this->checkSlashOnUrlEnd($this->acquiring_url);
        $this->url_init = $this->acquiring_url . 'Init/';
        $this->url_cancel = $this->acquiring_url . 'Cancel/';
        $this->url_confirm = $this->acquiring_url . 'Confirm/';
        $this->url_get_state = $this->acquiring_url . 'GetState/';
    }

    /**
     * Adding slash on end of url string if not there
     *
     * @return url string
     */
    private function checkSlashOnUrlEnd($url)
    {
        if ($url[strlen($url) - 1] !== '/') {
            $url .= '/';
        }
        return $url;
    }

    /**
     * Check payment array for all keys is isset
     *
     * @param  array for checking
     * @return [bool]
     */
    private function paymentArrayChecked(array $array_for_check)
    {
        $keys = [
            'OrderId',
            'Amount',
            'Email',
            'Phone',
            'Name',
            'Email',
            'Phone',
            'Taxation',
        ];
        return $this->allKeysIsExistInArray($keys, $array_for_check);
    }

    /**
     * Check items array for all keys is isset
     *
     * @param  array for checking
     * @return [bool]
     */
    private function itemsArrayChecked(array $array_for_check)
    {
        $keys = ['Name', 'Price', 'NDS', 'Quantity'];
        return $this->allKeysIsExistInArray($keys, $array_for_check);
    }

    /**
     * Checking for existing all $keys in $arr
     *
     * @param  array $keys - array of keys
     * @param  array $arr - checked array
     * @return [bool]
     */
    private function allKeysIsExistInArray(array $keys, array $arr)
    {
        return (bool) !array_diff_key(array_flip($keys), $arr);
    }

    /**
     * return protected propertys
     * @param  [mixed] $property name
     * @return [mixed]           value
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
?>
