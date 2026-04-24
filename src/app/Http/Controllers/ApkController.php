<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ApkController extends Controller
{
    public function download()
    {
        return response()->file(base_path('/sms-app.apk') ,[
            'Content-Type'=>'application/vnd.android.package-archive',
        ]) ;
    }
}
