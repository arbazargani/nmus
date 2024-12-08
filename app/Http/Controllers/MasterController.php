<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function dispatchNotif(Request $request, $title) {
        $title = base64_decode($title);
        Notification::title('منگنه')
        ->message("خبر $title منگنه خورد!")
        ->show();
    }
}
