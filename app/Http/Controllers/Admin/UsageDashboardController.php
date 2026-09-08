<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyAssignment;
use App\Models\UsageEvent;
use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Contracts\View\View;

class UsageDashboardController extends Controller
{
    public function __invoke(UsageAnalytics $analytics): View
    {
        $users = User::query()
            ->where('is_admin', false)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'summary' => $analytics->summarize($user),
            ]);

        return view('admin.usage.index', [
            'users' => $users,
            'registeredUsers' => $users->count(),
            'activeUsers7Days' => UsageEvent::query()
                ->whereHas('user', fn ($query) => $query->where('is_admin', false))
                ->where('occurred_at', '>=', now()->subDays(6)->startOfDay())
                ->distinct('user_id')
                ->count('user_id'),
            'surveyStatusCounts' => SurveyAssignment::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }
}
