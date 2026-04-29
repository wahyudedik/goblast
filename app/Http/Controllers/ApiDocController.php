<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ApiDocController extends Controller
{
    /**
     * Display the API documentation page.
     */
    public function __invoke(): View
    {
        $baseUrl = rtrim(config('app.url'), '/').'/api/v1';

        return view('api-docs.index', [
            'baseUrl' => $baseUrl,
        ]);
    }
}
