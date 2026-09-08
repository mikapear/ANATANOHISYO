<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LifeGoal;
use App\Models\Project;
use App\Models\Todo;
use App\Models\Treatment;
use App\Services\SecretaryPrompt;
use App\Services\UsageRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today();
        $nowTime = Carbon::now()->format('H:i:s');

        $incompleteWithDueDate = Todo::query()
            ->where('user_id', $user->id)
            ->where('is_completed', false)
            ->whereNotNull('due_date');

        $overdueTodos = (clone $incompleteWithDueDate)
            ->where(function ($query) use ($today, $nowTime) {
                $query->whereDate('due_date', '<', $today)
                    ->orWhere(function ($inner) use ($today, $nowTime) {
                        $inner->whereDate('due_date', $today)
                            ->whereNotNull('due_time')
                            ->where('due_time', '<', $nowTime);
                    });
            })
            ->orderBy('due_date')->orderBy('due_time')->get();

        $todayDueTodos = (clone $incompleteWithDueDate)
            ->whereDate('due_date', $today)
            ->where(fn ($query) => $query->whereNull('due_time')->orWhere('due_time', '>=', $nowTime))
            ->orderBy('due_time')->get();

        $completedTodayTodos = Todo::query()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereDate('completed_at', $today)
            ->orderByDesc('completed_at')->get();

        $todayActivityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereDate('performed_on', $today)
            ->orderBy('performed_at')->orderByDesc('created_at')->get();

        $todayTreatments = Treatment::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_on', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at')
            ->orderBy('name')
            ->get();

        $activeProjects = Project::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('due_date')->orderBy('name')
            ->get();

        $todayGoals = LifeGoal::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with([
                'project:id,name',
                'entries' => fn ($query) => $query->whereDate('recorded_on', $today),
            ])
            ->orderByRaw("case time_of_day when 'morning' then 1 when 'noon' then 2 when 'evening' then 3 when 'bedtime' then 4 else 5 end")
            ->orderBy('title')
            ->get()
            ->filter(fn ($goal) => $goal->isScheduledFor($today))
            ->values();
        $activeProjects->load([
            'todos' => fn ($query) => $query
                ->where(function ($todoQuery) use ($today) {
                    $todoQuery->where(function ($openQuery) use ($today) {
                        $openQuery->where('is_completed', false)->whereDate('due_date', $today);
                    })->orWhere(function ($doneQuery) use ($today) {
                        $doneQuery->where('is_completed', true)->whereDate('completed_at', $today);
                    });
                })
                ->orderBy('is_completed')->orderBy('due_time'),
            'activityLogs' => fn ($query) => $query
                ->whereDate('performed_on', $today)
                ->orderBy('performed_at')->orderByDesc('created_at'),
            'checkinItems' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['entries' => fn ($entryQuery) => $entryQuery->whereDate('checked_on', $today)]),
        ]);

        $todayProjects = $activeProjects
            ->each(function ($project) use ($today) {
                $project->setRelation(
                    'checkinItems',
                    $project->checkinItems->filter(fn ($item) => $item->isScheduledFor($today))->values()
                );
            })
            ->values();

        $secretaryPrompt = app(SecretaryPrompt::class)->forToday(
            $user,
            $todayActivityLogs,
            $todayProjects,
            $todayGoals,
            $todayTreatments
        );
        $secretaryMessage = $secretaryPrompt['message'];

        app(UsageRecorder::class)->record($user, 'today.view');
        app(UsageRecorder::class)->record(
            $user,
            'secretary.prompt_shown',
            context: ['prompt_key' => $secretaryPrompt['key']]
        );

        return view('dashboard', compact(
            'today',
            'overdueTodos',
            'todayDueTodos',
            'completedTodayTodos',
            'todayActivityLogs',
            'todayTreatments',
            'activeProjects',
            'todayProjects',
            'todayGoals',
            'secretaryMessage'
        ));
    }
}
