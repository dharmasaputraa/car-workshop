<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ThrottlesRequests;

abstract class Controller
{
    use ThrottlesRequests;
}
