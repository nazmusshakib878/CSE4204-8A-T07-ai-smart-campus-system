<?php
return [
 'paths'=>['api/*','sanctum/csrf-cookie','storage/*'],
 'allowed_methods'=>['*'],
 'allowed_origins'=>array_values(array_filter(array_map('trim',explode(',',env('CORS_ALLOWED_ORIGINS','http://localhost:5173'))))),
 'allowed_origins_patterns'=>[],
 'allowed_headers'=>['Accept','Authorization','Content-Type','Origin','X-Requested-With'],
 'exposed_headers'=>[],
 'max_age'=>(int)env('CORS_MAX_AGE',3600),
 'supports_credentials'=>(bool)env('CORS_SUPPORTS_CREDENTIALS',false),
];
