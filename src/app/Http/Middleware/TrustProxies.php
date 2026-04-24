<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $headers = [
        \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR,
        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST,
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT,
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
    ];

    protected $proxies = '*'; // или массив IP прокси, если ограничиваешь
}
