<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LifeGoal;
use App\Models\Project;
use App\Models\Todo;
use App\Models\Treatment;
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

        $latestCondition = $todayActivityLogs->whereNotNull('condition')->last()?->condition;
        $medicationItems = $todayProjects->flatMap->checkinItems->where('kind', 'medication');
        $medicationTotal = $medicationItems->sum(fn ($item) => count($item->medication_timings ?? []));
        $medicationRecorded = $medicationItems->sum(fn ($item) => $item->entries->count());
        $medicationCompleted = $medicationItems->sum(
            fn ($item) => $item->entries->where('status', 'taken')->count()
        );
        $unrecordedGoal = $todayGoals->first(fn ($goal) => $goal->entries->isEmpty());
        $recordedGoalCount = $todayGoals->filter(fn ($goal) => $goal->entries->isNotEmpty())->count();

        $secretaryMessage = match (true) {
            $latestCondition === 'hard' => '今日はつらさがあるのですね。無理をせず、休むことも今日の大切な予定にしましょう。気になる症状があるときは、医療者に相談してくださいね。',
            $medicationTotal > $medicationRecorded => '今日のお薬に、まだ確認していない分があります。飲んだかどうかを、落ち着いて一緒に確認しましょう。',
            $medicationTotal > 0 && $medicationCompleted < $medicationTotal => '今日のお薬の状況を記録できましたね。飲み忘れや気になることがあるときは、必要に応じて医療者に相談してください。',
            $medicationTotal > 0 && $unrecordedGoal !== null => "今日のお薬の確認ができましたね。体調に余裕があれば、次は「{$unrecordedGoal->title}」をしてみませんか。無理な日は休んで大丈夫です。",
            $unrecordedGoal !== null => "今日の目標に「{$unrecordedGoal->title}」があります。今の調子に合わせて、できる範囲で取り組んでみましょう。",
            $recordedGoalCount > 0 => '今日の目標を記録できましたね。できた日も、少しできた日も、休んだ日も、どれも大切な歩みです。',
            default => "こんにちは、{$user->name}さん。今日の予定を一緒に確認しましょう。",
        };

        app(UsageRecorder::class)->record($user, 'today.view');

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
