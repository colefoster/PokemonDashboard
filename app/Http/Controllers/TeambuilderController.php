<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeambuilderController extends Controller
{
    public function index()
    {
        return view('teambuilder.index');
    }
}