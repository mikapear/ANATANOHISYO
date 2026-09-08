<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use App\Models\SurveyDefinition;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;

class SurveyScheduler
{
    private const PHASES = [
        'baseline' => 0,
        'week4' => 28,
        'week8' => 56,
    ];

    public function assign(User $user, SurveyDefinition $definition, CarbonInterface $startDate): Collection
    {
        if (! $definition->is_active) {
            throw new DomainException('Inactive survey definitions cannot be assigned.');
        }

        $start = CarbonImmutable::instance($startDate)->startOfDay();

        return collect(self::PHASES)->map(function (int $days, string $phase) use ($user, $definition, $start) {
            $dueOn = $start->addDays($days)->toDateString();

            return SurveyAssignment::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'survey_definition_id' => $definition->id,
                    'phase' => $phase,
                ],
                [
                    'due_on' => $dueOn,
                    'available_from' => $dueOn,
                    'status' => 'pending',
                ]
            );
        })->values();
    }
}
