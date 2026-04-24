<?php

namespace App\Http\Controllers\MerchantSupport;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index()
    {
        /**
         * @var User $user
         */
        if (auth()->user()->hasRole('Super Admin')) {
            $user = auth()->user();
        } else {
            $user = auth()->user()->merchant;
        }
        $token = $user->api_access_token;

        return Inertia::render('MerchantSupport/Integration/Index', compact('token'));
    }
}
