<?php
// app/Http/Controllers/WilayahLookupController.php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class WilayahLookupController extends Controller
{
    public function provinsi()
    {
        $result = Location::query()
            ->where('status', 1)
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 3, 8) = '00000000'")
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 2) != '00'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return response()->json($result);
    }

    public function kabupaten(Request $request)
    {
        $request->validate(['provinsi_id' => 'required|integer']);
        $code = substr(str_pad($request->provinsi_id, 10, '0', STR_PAD_LEFT), 0, 2);

        $result = Location::query()
            ->where('status', 1)
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 2) = ?", [$code])
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 5, 6) = '000000'")
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 3, 2) != '00'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return response()->json($result);
    }

    public function kecamatan(Request $request)
    {
        $request->validate(['kabupaten_id' => 'required|integer']);
        $code = substr(str_pad($request->kabupaten_id, 10, '0', STR_PAD_LEFT), 0, 4);

        $result = Location::query()
            ->where('status', 1)
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 4) = ?", [$code])
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 8, 3) = '000'")
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 5, 3) != '000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return response()->json($result);
    }

    public function desa(Request $request)
    {
        $request->validate(['kecamatan_id' => 'required|integer']);
        $code = substr(str_pad($request->kecamatan_id, 10, '0', STR_PAD_LEFT), 0, 7);

        $result = Location::query()
            ->where('status', 1)
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 1, 7) = ?", [$code])
            ->whereRaw("SUBSTRING(LPAD(location_id, 10, '0'), 8, 3) != '000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return response()->json($result);
    }
}