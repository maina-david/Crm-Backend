<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function get_country()
    {
        return Country::get(["name","code"]);
    }
}
