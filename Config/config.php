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
    'default_layout' => env('DEFAULT_LAYOUT', 'flowbite'),
    'use' => [
        'spotlight' => env('USE_SPOTLIGHT', config('app.env') == 'local')
    ],
    'modules' => [
        'App' => 'DviDev/app-module',
        'AppBuilder' => 'DviDev/appbuilder-module',
        'Chat' => 'DviDev/chat-module',
        'DBMap' => 'DviDev/dbmap-module',
        'DvUi' => 'DviDev/dvui-module',
        'Flowbite' => 'DviDev/flowbite-module',
        'Insur' => 'DviDev/insur-module',
        'Link' => 'DviDev/link-module',
        'Lte' => 'DviDev/adminlte_blade-module',
        'MercadoPago' => 'DviDev/mercado_pago-module',
        'Permission' => 'DviDev/permission-module',
        'Person' => 'DviDev/person-module',
        'Post' => 'DviDev/post-module',
        'Project' => 'DviDev/project-module',
        'Social' => 'DviDev/social-module',
        'Solicitation' => 'DviDev/solicitation-module',
        'Store' => 'DviDev/store-module',
        'Task' => 'DviDev/task-module',
        'View' => 'DviDev/view_structure-module',
        'Workspace' => 'DviDev/workspace-module',
    ],
];
