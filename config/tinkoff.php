<?php

return [
    "acquiring_url" => env(
        "TINKOFF_ACQUIRING_URL",
        "https://securepay.tinkoff.ru/v2/"
    ),
    "terminal_id" => env("TINKOFF_TERMINAL_ID"),
    "secret_key" => env("TINKOFF_SECRET_KEY"),
];
