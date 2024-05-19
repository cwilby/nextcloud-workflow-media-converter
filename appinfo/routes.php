<?php

return ['routes' => [
	['verb' => 'GET', 'url' => '/conversion-batches', 'name' => 'conversionBatches#index'],
	['verb' => 'GET', 'url' => '/conversion-batches/{id}', 'name' => 'conversionBatches#show'],
	['verb' => 'POST', 'url' => '/conversion-batches', 'name' => 'conversionBatches#create'],
	['verb' => 'DELETE', 'url' => '/conversion-batches/{id}', 'name' => 'conversionBatches#delete'],
	['verb' => 'GET', 'url' => '/admin-settings', 'name' => 'adminSettings#getSettings'],
	['verb' => 'PUT', 'url' => '/admin-settings', 'name' => 'adminSettings#updateSettings'],
	['verb' => 'GET', 'url' => '/personal-settings', 'name' => 'personalSettings#getSettings'],
	['verb' => 'PUT', 'url' => '/personal-settings', 'name' => 'personalSettings#updateSettings']
]];
