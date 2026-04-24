<?php

namespace App\Http\Controllers\API\Payout;

use App\Http\Controllers\Controller;

class PayoutOfferController extends Controller
{
    public function __construct()
    {
        //TODO
        if (! auth()->user()->payouts_enabled) {
            abort(403);
        }
    }

    public function index()
    {
        $offersMenu = services()->payout()->getOffersMenu();

        return response()->success($offersMenu);
    }
}
