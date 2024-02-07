<?php

return [
    'aws' => [
        'sso_profile' => env('AWS_SSO_PROFILE', 'default'),
        'role_arn' => env('AWS_ROLE_ARN'),
        'sns_topic_arn' => env('AWS_SNS_TOPIC_ARN')
    ]
];
