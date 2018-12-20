<?php

return [
    'key' => 'NetTrainService',
    'name' => '网络培训服务',
    'tp_config' => [
        'URL_CASE_INSENSITIVE' => false,
        'SERVICE_STATUS' => [
            '1' => 'ARRANGED',
            '3' => 'START',
            '4' => 'FINISH',
            '6' => 'AUDITTING',
            '7' => 'REGISTING_STEP',
            '8' => 'BACKEND_TRAINNING_STEP',
            '9' => 'CHECK_EXECUTED'
        ]
    ],
    'setting' => null,
    'privileges' => [
        'index', 'remoteServiceView', 'addHomeDemand', 'insertHomeDemand', 'update', 
        'edit', 'delete', 'search', 'me', 'under', 'remore', 'scheduling', 'schedu_update', 
        'schedu_edit', 'theDoorServer', 'teacher', 'teacherxiede', 'drop', 'visit', 
        'huifang', 'huifangbianxie', 'callOn', 'verify', 'verify_edit', 'verify_update', 
        'check', 'aboveStatistics', 'theDoorAnalysis', 'trainingAbility', 'getCustomerAnswer'
    ]
];