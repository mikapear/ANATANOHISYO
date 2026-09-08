<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyDefinition;
use App\Models\User;
use App\Services\SurveyScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurveyAssignmentController extends Controller
{
    public function store(Request $request, SurveyDefinition $surveyDefinition, SurveyScheduler $scheduler): RedirectResponse
    {
        if (! $surveyDefinition->is_active || ! $surveyDefinition->questions()->exists()) {
            return back()->withErrors(['survey' => '有効で質問が登録されたアンケートだけを割り当てられます。']);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_admin', false)),
            ],
            'start_date' => ['required', 'date'],
        ]);

        $users = User::query()->whereIn('id', $validated['user_ids'])->where('is_admin', false)->get();
        $startDate = Carbon::parse($validated['start_date']);

        DB::transaction(function () use ($users, $surveyDefinition, $scheduler, $startDate) {
            foreach ($users as $user) {
                $scheduler->assign($user, $surveyDefinition, $startDate);
            }
        });

        return redirect()->route('admin.surveys.index', ['survey' => $surveyDefinition])
            ->with('status', "{$users->count()}人へ開始時・4週・8週のアンケートを割り当てました。");
    }
}
