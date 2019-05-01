<?php

return [
    'api-suffix' => env('ODOO_API_SUFFIX','xmlrpc'),     // 'xmlrpc' from version 7.0 and earlier, 'xmlrpc/2' from version 8.0 and above.

    //Credentials
    'host'       => env('ODOO_HOST'),
    'db'         => env('ODOO_DB'),
    'username'   => env('ODOO_USERNAME'),
    'password'   => env('ODOO_PASSWORD'),
];