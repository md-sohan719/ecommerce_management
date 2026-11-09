<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Page size from ?per_page=, clamped to a sane range.
     */
    protected function perPage(Request $request, int $default = 10, int $max = 100): int
    {
        return max(1, min($max, $request->integer('per_page', $default)));
    }
}
