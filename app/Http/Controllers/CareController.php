<?php

namespace App\Http\Controllers;

use App\Models\CheckinItem;
use App\Models\Project;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $asNeededMedications = CheckinItem::where('kind', 'medication')->where('is_as_needed', true)->where('is_active', true)
            ->whereHas('project', fn ($query) => $query->where('user_id', $userId))
            ->with(['asNeededUsages' => fn ($query) => $query->whereDate('used_at', today())->latest('used_at')])
            ->orderBy('title')->get();

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

        return view('care.index', compact('medicationProjects', 'asNeededMedications', 'checkinProject', 'upcomingTreatments'));
    }
}
