<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use Inertia\Inertia;
use Inertia\Response;

class ParcelPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ParcelsPage');
    }

    public function show(Parcel $parcel): Response
    {
        return Inertia::render('ParcelDetailPage', ['parcelId' => $parcel->id]);
    }
}
