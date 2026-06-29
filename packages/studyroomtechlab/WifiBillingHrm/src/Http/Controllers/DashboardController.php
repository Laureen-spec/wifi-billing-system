<?php

namespace StudyRoomTechLab\WifiBillingHrm\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('view-wifi-dashboard') || auth()->user()?->can('manage-wifi-dashboard'), 403);

        return Inertia::render('WifiBillingHrm/Dashboard');
    }
}
