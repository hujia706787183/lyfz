<?php

return [
    'register' => [
        'product' => [
            'index' => [
                'PlutinumList' => '管理软件产品列表'
            ]
        ]
    ],
    'events' => [
        'ProductAddToCustomer' => [
            'ProductAddToCustomerHandler'
        ],
        'SetProductInfo' => [
            'SetProductEventHandler'
        ]
    ]

];
