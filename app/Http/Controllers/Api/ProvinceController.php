<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of all provinces.
     */
    public function index(Request $request)
    {
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return response()->json($provinces);
    }
}
