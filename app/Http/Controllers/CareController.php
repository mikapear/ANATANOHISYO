<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $medicationProjects = Project::where('user_id', $userId)
            ->where('uses_checkins', true)
            ->whereHas('checkinItems', fn ($query) => $query->where('kind', 'medication'))
            ->with(['checkinItems' => fn ($query) => $query->where('kind', 'medication')->where('is_active', true)->orderBy('position')])
            ->orderBy('name')
            ->get();

        $checkinProject = Project::where('user_id', $userId)
            ->where('uses_checkins', true)
            ->where('status', 'active')
            ->orderBy('name')
            ->first();

        $upcomingTreatments = Treatment::where('user_id', $userId)
            ->whereDate('scheduled_on', '>=', today())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_on')
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get();

        return view('care.index', compact('medicationProjects', 'checkinProject', 'upcomingTreatments'));
    }
}