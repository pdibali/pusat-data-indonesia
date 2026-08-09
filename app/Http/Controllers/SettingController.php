<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function toggleMidtrans(Request $request)
    {
        $current = Setting::get('midtrans_enabled', true);
        Setting::set('midtrans_enabled', !$current);

        return back()->with('success', 'Status Midtrans berhasil diubah.');
    }
}