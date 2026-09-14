<?php

declare(strict_types=1);

return [
    'google_maps_api_key' => \env('GOOGLE_MAPS_API_KEY'),
    'google_maps_map_id' => \env('GOOGLE_MAPS_MAP_ID', 'DEMO_MAP_ID'),
    'default_center' => [
        'latitude' => 55.40628811114651,
        'longitude' => 9.186381580256743,
    ],
    'default_zoom' => 9,
];
