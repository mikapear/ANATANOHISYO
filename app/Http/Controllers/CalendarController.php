<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CheckinEntry;
use App\Models\LifeGoal;
use App\Models\LifeGoalEntry;
use App\Models\Project;
use App\Models\Todo;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->string('month')->toString();
        try {
            $current = $month !== '' ? Carbon::createFromFormat('!Y-m', $month) : Carbon::today()->startOfMonth();
        } catch (\Throwable) {
            $current = Carbon::today()->startOfMonth();
        }

        $current = $current->startOfMonth();
        $start = $current->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $current->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $userId = $request->user()->id;

        $projects = Project::where('user_id', $userId)
            ->where('uses_calendar', true)
            ->orderBy('name')->get();
        $projectId = $projects->firstWhere('id', (int) $request->integer('project_id'))?->id;

        $todoQuery = Todo::where('user_id', $userId)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()]);
        $logQuery = ActivityLog::where('user_id', $userId)
            ->whereBetween('performed_on', [$start->toDateString(), $end->toDateString()]);

        if ($projectId) {
            $todoQuery->where('project_id', $projectId);
            $logQuery->where('project_id', $projectId);
        }

        $todos = $todoQuery->orderBy('due_time')->get()->groupBy(fn ($todo) => $todo->due_date->toDateString());
        $logs = $logQuery->orderBy('performed_at')->get()->groupBy(fn ($log) => $log->performed_on->toDateString());

        $treatments = Treatment::where('user_id', $userId)
            ->whereBetween('scheduled_on', [$start->toDateString(), $end->toDateString()])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn ($treatment) => $treatment->scheduled_on->toDateString());
        $checkinProjects = Project::where('user_id', $userId)
            ->where('uses_calendar', true)
            ->where('uses_checkins', true)
            ->when($projectId, fn ($query) => $query->whereKey($projectId))
            ->with(['checkinItems' => fn ($query) => $query->where('is_active', true)])
            ->get();
        $checkinItems = $checkinProjects->flatMap->checkinItems;
        $checkinItemIds = $checkinItems->pluck('id');
        $checkinEntries = CheckinEntry::where('user_id', $userId)
            ->whereIn('checkin_item_id', $checkinItemIds)
            ->whereBetween('checked_on', [$start->toDateString(), $end->toDateString()])
            ->with('item.project')
            ->get()
            ->groupBy(fn ($entry) => $entry->checked_on->toDateString());
        $checkinTotal = $checkinItems->sum(fn ($item) => $item->kind === 'medication' ? count($item->medication_timings ?? []) : 1);

        $lifeGoals = LifeGoal::where('user_id', $userId)
            ->where('is_active', true)
            ->with('project:id,name')
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('title')
            ->get();
        $lifeGoalEntries = LifeGoalEntry::where('user_id', $userId)
            ->whereIn('life_goal_id', $lifeGoals->pluck('id'))
            ->whereBetween('recorded_on', [$start->toDateString(), $end->toDateString()])
            ->with('lifeGoal')
            ->get()
            ->groupBy(fn ($entry) => $entry->recorded_on->toDateString());
        $selectedValue = $request->string('date')->toString();
        try {
            $selected = $selectedValue !== ''
                ? Carbon::createFromFormat('!Y-m-d', $selectedValue)
                : Carbon::today();
        } catch (\Throwable) {
            $selected = Carbon::today();
        }
        if ($selectedValue === '' && ! $selected->isSameMonth($current)) {
            $selected = null;
        }        if ($selected && ($selected->lt($start) || $selected->gt($end))) {
            $selected = null;
        }

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = $day->copy();
        }

        return view('calendar.index', compact(
            'current', 'days', 'todos', 'logs', 'treatments', 'selected', 'projects', 'projectId',
            'checkinProjects', 'checkinItems', 'checkinEntries', 'checkinTotal',
            'lifeGoals', 'lifeGoalEntries'
        ));
    }
}
