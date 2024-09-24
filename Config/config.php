<?php

return [
    'name' => 'Base',
    'date_format' => config('app.locale') == 'pt_BR' ? 'd/m/Y' : 'Y-m-d',
    'date_time_format' => config('app.locale') == 'pt_BR' ? 'd/m/Y H:i' : 'Y-m-d H:i',
    'time_format' => 'H:i:s',
    'cache_keys' => [
        'project_app_name' => 'seeder::project::' . config('app.name'),
        'user_develop' => 'seeder::user::developer',
    ],
    'use' => [
        'spotlight' => env('USE_SPOTLIGHT', config('app.env') == 'local')
    ]
];
