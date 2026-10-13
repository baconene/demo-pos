<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function show(string $id): Response
    {
        return Inertia::render('Menu', [
            'id' => $id,
        ]);
    }
}
