<?php

return [
    'lock_timeout'      => env('CAS_LOCK_TIMEOUT', 5000),
    'ticket_expire'     => env('CAS_TICKET_EXPIRE', 300),
    'ticket_len'        => env('CAS_TICKET_LEN', 32),
    'pg_ticket_expire'  => env('CAS_PROXY_GRANTING_TICKET_EXPIRE', 7200),
    'pg_ticket_len'     => env('CAS_PROXY_GRANTING_TICKET_LEN', 64),
    'pg_ticket_iou_len' => env('CAS_PROXY_GRANTING_TICKET_IOU_LEN', 64),
    'verify_ssl'        => env('CAS_VERIFY_SSL', true),
    'client_protocal'   => env('CAS_CLIENT_PROTOCAL'),
    'user_table'        => [
        'id'    => 'id',
        'name'  => 'cas_users',
        'model' => \App\Models\CasUser::class, //change to your user model class
    ],
    'router'            => [
        'prefix'      => 'cas',//第一个cas是用户输入的cas对应nginx中的目录cas目录，第二个cas代表/cas/目录下laravel的控制器
        'name_prefix' => 'cas.',
    ],
    'middleware'        => [
        'common' => 'web',
        'auth'   => 'auth',
    ],
];
