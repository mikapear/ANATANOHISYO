<?php

namespace App\Http\Controllers;

use App\Services\UsageAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UsageDashboardController extends Controller
{
    public function __invoke(Request $request, UsageAnalytics $analytics): View
    {
        return view('usage.index', [
            'summary' => $analytics->summarize($request->user()),
            'eventLabels' => [
                'today.view' => 'Todayを見た',
                'todo.completed' => '予定を完了した',
                'medication.checked' => '服薬を記録した',
                'goal.recorded' => '目標を記録した',
                'secretary.prompt_shown' => 'やさしい声かけを表示した',
                'survey.started' => 'アンケートを開始した',
                'survey.saved' => 'アンケートを途中保存した',
                'survey.completed' => 'アンケートを完了した',
            ],
        ]);
    }
}
