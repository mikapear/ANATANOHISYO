<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class SecretaryPrompt
{
    public function forToday(
        User $user,
        Collection $activityLogs,
        Collection $projects,
        Collection $goals,
        Collection $treatments
    ): array {
        $latestCondition = $activityLogs->whereNotNull('condition')->last()?->condition;
        $medicationItems = $projects->flatMap->checkinItems->where('kind', 'medication');
        $medicationTotal = $medicationItems->sum(fn ($item) => count($item->medication_timings ?? []));
        $medicationRecorded = $medicationItems->sum(fn ($item) => $item->entries->count());
        $medicationTaken = $medicationItems->sum(
            fn ($item) => $item->entries->where('status', 'taken')->count()
        );
        $unrecordedGoal = $goals->first(fn ($goal) => $goal->entries->isEmpty());
        $recordedGoalCount = $goals->filter(fn ($goal) => $goal->entries->isNotEmpty())->count();
        $todayTreatment = $treatments->first();

        return match (true) {
            $latestCondition === 'hard' => [
                'key' => 'condition.rest',
                'message' => '今日はつらさがあるのですね。無理をせず、休むことも今日の大切な予定にしましょう。気になる症状があるときは、医療者に相談してくださいね。',
            ],
            $medicationTotal > $medicationRecorded => [
                'key' => 'medication.pending',
                'message' => '今日のお薬に、まだ確認していない分があります。飲んだかどうかを、落ち着いて一緒に確認しましょう。',
            ],
            $medicationTotal > 0 && $medicationTaken < $medicationTotal => [
                'key' => 'medication.attention',
                'message' => '今日のお薬の状況を記録できましたね。飲み忘れや気になることがあるときは、必要に応じて医療者に相談してください。',
            ],
            $todayTreatment !== null => [
                'key' => 'appointment.today',
                'message' => "今日は「{$todayTreatment->name}」の予定があります。相談したいことがあれば、診察前のメモも確認しておきましょう。",
            ],
            $medicationTotal > 0 && $unrecordedGoal !== null => [
                'key' => 'goal.after_medication',
                'message' => "今日のお薬の確認ができましたね。体調に余裕があれば、次は「{$unrecordedGoal->title}」をしてみませんか。無理な日は休んで大丈夫です。",
            ],
            $unrecordedGoal !== null => [
                'key' => 'goal.prompt',
                'message' => "今日の目標に「{$unrecordedGoal->title}」があります。今の調子に合わせて、できる範囲で取り組んでみましょう。",
            ],
            $recordedGoalCount > 0 => [
                'key' => 'goal.recorded',
                'message' => '今日の目標を記録できましたね。できた日も、少しできた日も、休んだ日も、どれも大切な歩みです。',
            ],
            default => [
                'key' => 'welcome',
                'message' => "こんにちは、{$user->name}さん。今日の予定を一緒に確認しましょう。",
            ],
        };
    }
}
