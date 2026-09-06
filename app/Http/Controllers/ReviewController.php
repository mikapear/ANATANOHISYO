<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CheckinEntry;
use App\Models\CheckinItem;
use App\Models\LifeGoal;
use App\Models\LifeGoalEntry;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() === 'month' ? 'month' : 'week';

        try {
            $anchor = $request->filled('date')
                ? Carbon::createFromFormat('!Y-m-d', $request->string('date')->toString())
                : Carbon::today();
        } catch (\Throwable) {
            $anchor = Carbon::today();
        }

        if ($period === 'month') {
            $start = $anchor->copy()->startOfMonth();
            $end = $anchor->copy()->endOfMonth();
            $previous = $start->copy()->subMonth();
            $next = $start->copy()->addMonth();
        } else {
            $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
            $previous = $start->copy()->subWeek();
            $next = $start->copy()->addWeek();
        }

        $user = $request->user();
        $medicationItems = CheckinItem::query()
            ->where('kind', 'medication')
            ->where('is_active', true)
            ->whereHas('project', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'active'))
            ->get();

        $medicationScheduled = 0;
        $goals = LifeGoal::where('user_id', $user->id)->where('is_active', true)->get();
        $goalScheduled = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            foreach ($medicationItems as $item) {
                if ($item->isScheduledFor($day)) {
                    $medicationScheduled += $item->scheduledSlotCount();
                }
            }
            foreach ($goals as $goal) {
                if ($goal->isScheduledFor($day)) {
                    $goalScheduled++;
                }
            }
        }

        $medicationCompleted = CheckinEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('checkin_item_id', $medicationItems->pluck('id'))
            ->whereBetween('checked_on', [$start->toDateString(), $end->toDateString()])
            ->count();

        $goalEntries = LifeGoalEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('recorded_on', [$start->toDateString(), $end->toDateString()])
            ->with('lifeGoal')
            ->get();
        $goalStatusCounts = [
            'completed' => $goalEntries->where('status', 'completed')->count(),
            'partial' => $goalEntries->where('status', 'partial')->count(),
            'rest' => $goalEntries->where('status', 'rest')->count(),
        ];
        $exerciseEntries = $goalEntries->filter(fn ($entry) => $entry->lifeGoal?->category === 'exercise');
        $nutritionEntries = $goalEntries->filter(fn ($entry) => $entry->lifeGoal?->category === 'nutrition');

        $activityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('performed_on', [$start->toDateString(), $end->toDateString()])
            ->get();
        $conditionCounts = [
            'good' => $activityLogs->where('condition', 'good')->count(),
            'usual' => $activityLogs->where('condition', 'usual')->count(),
            'hard' => $activityLogs->where('condition', 'hard')->count(),
        ];

        $completedTodos = Todo::query()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->count();

        $medicationRate = $medicationScheduled > 0
            ? min(100, (int) round($medicationCompleted / $medicationScheduled * 100))
            : null;
        $goalRecordRate = $goalScheduled > 0
            ? min(100, (int) round($goalEntries->count() / $goalScheduled * 100))
            : null;

        $exerciseTotals = $exerciseEntries
            ->filter(fn ($entry) => $entry->actual_amount !== null && $entry->lifeGoal?->target_unit)
            ->groupBy(fn ($entry) => $entry->lifeGoal->target_unit)
            ->map(fn ($entries) => $entries->sum(fn ($entry) => (float) $entry->actual_amount));
        $reviewMessage = match (true) {
            $conditionCounts['hard'] > 0 => 'つらい日も記録に残せましたね。できた数だけでなく、休む選択ができたことも大切な歩みです。',
            $goalStatusCounts['rest'] > 0 => '休んだ日も含めて、自分の調子に合わせて過ごせています。次の期間も無理のない目標に整えていきましょう。',
            $medicationRate !== null && $medicationRate >= 80 => 'お薬の確認を丁寧に続けられていますね。運動や食事の記録も、できる範囲で積み重ねていきましょう。',
            $goalEntries->isNotEmpty() || $completedTodos > 0 => 'この期間にも、いくつもの歩みを残せました。少しできたことも、次につながる大切な記録です。',
            default => 'まだ記録が少ない期間です。まずは今日の服薬や体調から、ひとつだけ残してみましょう。',
        };

        return view('reviews.index', compact(
            'period', 'start', 'end', 'previous', 'next',
            'medicationScheduled', 'medicationCompleted', 'medicationRate',
            'goalScheduled', 'goalEntries', 'goalStatusCounts', 'goalRecordRate',
            'exerciseEntries', 'exerciseTotals', 'nutritionEntries', 'activityLogs', 'conditionCounts',
            'completedTodos', 'reviewMessage'
        ));
    }
}