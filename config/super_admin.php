<?php

return [
    'name' => env('ADMIN_NAME', env('SUPER_ADMIN_NAME')),
    'email' => env('ADMIN_EMAIL', env('SUPER_ADMIN_EMAIL')),
    'password' => env('ADMIN_PASSWORD', env('SUPER_ADMIN_PASSWORD')),
];
