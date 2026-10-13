<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class FinancialPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('FinancialPage');
    }
}
