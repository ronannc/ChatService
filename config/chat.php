<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin API Key
    |--------------------------------------------------------------------------
    |
    | Chave usada exclusivamente pelos endpoints administrativos do chat
    | service (ex.: registro de sistemas integrados). Distinta de qualquer
    | token de sistema emitido/validado via JWKS.
    |
    */
    'admin_api_key' => env('CHAT_ADMIN_API_KEY'),
];
