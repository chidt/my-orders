<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class WardController extends Controller
{
    /**
     * Display a listing of wards for a specific province.
     */
    public function index(Request $request, Province $province)
    {
        $wards = $province->wards()->orderBy('name')->get(['id', 'name']);

        return response()->json($wards);
    }
}
