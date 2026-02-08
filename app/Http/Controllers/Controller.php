<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max((int) ($request->per_page ?? $default), 1), $max);
    }
}
