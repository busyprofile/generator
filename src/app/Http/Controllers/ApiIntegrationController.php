<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ApiIntegrationController extends Controller
{
    public function index()
    {
        $token = auth()->user()->api_access_token;

        return Inertia::render('Integration/Index', compact('token'));
    }
}
