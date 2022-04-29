# Simple Tinkoff bank acquiring library.
Простая библиотека для приема платежей через интернет для Тинькофф банк.

### Возможности

 * Генерация URL для оплаты товаров
 * Подттверждение платежа
 * Просмотр статуса платжа
 * Отмена платежа

### Установка

С помощью [Composer](https://getcomposer.org/):

```bash
composer require jumas-cola/laravel-tinkoff
```

Форк библиотеки kenvel/laravel-tinkoff

Подключение в контроллере:

```php
use Kenvel\Tinkoff;
```

## Примеры использования
### 1. Настройка

Добавить в файл .env и опубликовать конфигурацию через php artisan vendor:publish:
```php
#Tinkoff
TINKOFF_TERMINAL_ID=1111111111111DEMO
TINKOFF_SECRET_KEY=xxxxxxxxxxxxxxxx
```

### 2. Получить URL для оплаты
```php
//Подготовка массива с данными об оплате
$payment = [
    'OrderId'       => '123456',        //Ваш идентификатор платежа
    'Amount'        => '100',           //сумма всего платежа в рублях
    'Language'      => 'ru',            //язык - используется для локализации страницы оплаты
    'Description'   => 'Some buying',   //описание платежа
    'Email'         => 'user@email.com',//email покупателя
    'Phone'         => '89099998877',   //телефон покупателя
    'Name'          => 'Customer name', //Имя покупателя
    'Taxation'      => 'usn_income'     //Налогооблажение
];

//подготовка массива с покупками
$items[] = [
    'Name'  => 'Название товара',
    'Price' => '100',    //цена товара в рублях
    'NDS'   => 'vat20',  //НДС
];

//Получение url для оплаты
$paymentURL = Tinkoff::paymentURL($payment, $items);
```

### 3. Получить статус платежа
```php
//$payment_id Идентификатор платежа банка (полученый в пункте "2 Получить URL для оплаты")

$status = Tinkoff::getState($payment_id)
```

### 4. Отмена платежа
```php
$status = Tinkoff::cancelPayment($payment_id)
```

### 5. Подтверждение платежа
```php
$status = Tinkoff::confirmPayment($payment_id)
```

### 6. Проверка нотификации со стстусом платежа
```php
$is_valid = Tinkoff::checkNotification($request->all())
```

---

[![Donate button](https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=FGCHZNKKVG622&source=url)

*Если вы нашли этот проект полезным, пожалуйста сделайте небольшой донат - это поможет мне улучшить код*
