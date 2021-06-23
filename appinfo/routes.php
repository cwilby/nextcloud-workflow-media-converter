<?php

return [
    'routes' => [
        [
            'verb' => 'GET',
            'url' => '/batch-conversions',
            'name' => 'batchConversions#index',
        ],
        [
            'verb' => 'GET',
            'url' => '/batch-conversions/{id}',
            'name' => 'batchConversions#show',
        ],
        [
            'verb' => 'POST',
            'url' => '/batch-conversions',
            'name' => 'batchConversions#create',
        ],
        [
            'verb' => 'DELETE',
            'url' => '/batch-conversions/{id}',
            'name' => 'batchConversions#delete'
        ],
        [
            'verb' => 'GET',
            'url' => '/admin-settings',
            'name' => 'adminSettings#getSettings'
        ],
        [
            'verb' => 'PUT',
            'url' => '/admin-settings',
            'name' => 'adminSettings#updateSettings'
        ],
        [
            'verb' => 'GET',
            'url' => '/personal-settings',
            'name' => 'personalSettings#getSettings'
        ],
        [
            'verb' => 'PUT',
            'url' => '/personal-settings',
            'name' => 'personalSettings#updateSettings'
        ]
    ]
];
