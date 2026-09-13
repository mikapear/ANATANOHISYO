<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    private const TYPE_LABELS = [
        'consultation' => '診察',
        'chemotherapy' => '抗がん剤治療',
        'infusion' => '点滴',
        'injection' => '注射',
        'radiation' => '放射線治療',
        'procedure' => '処置・手術',
        'other' => 'その他',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $treatments = Treatment::where('user_id', $user->id)
            ->orderBy('scheduled_on')
            ->orderBy('scheduled_at')
            ->get();

        return view('treatments.index', compact('treatments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['name'] = $this->treatmentName($validated);
        $request->user()->treatments()->create($validated + ['status' => 'scheduled']);

        return back()->with('status', '治療予定を登録しました。');
    }

    public function update(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $validated = $this->validated($request);
        $validated['name'] = $this->treatmentName($validated);
        $treatment->update($validated);

        return back()->with('status', '治療予定を更新しました。');
    }

    public function updateStatus(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'completed', 'postponed', 'cancelled', 'changed'])],
        ]);
        $treatment->update($validated);

        return back()->with('status', '治療の状態を更新しました。');
    }

    public function updateSummary(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $validated = $request->validate([
            'visit_summary' => ['nullable', 'string', 'max:4000'],
        ]);
        $treatment->update($validated);

        return back()->with('status', '診察後のメモを保存しました。');
    }

    public function destroy(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $treatment->delete();

        return back()->with('status', '治療予定を削除しました。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'required_if:treatment_type,other', 'string', 'max:255'],
            'treatment_type' => ['required', Rule::in(array_keys(self::TYPE_LABELS))],
            'scheduled_on' => ['required', 'date'],
            'scheduled_at' => ['nullable', 'date_format:H:i'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'hospital' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'visit_summary' => ['nullable', 'string', 'max:4000'],
            'project_id' => ['prohibited'],
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'postponed', 'cancelled', 'changed'])],
        ]);
    }

    private function treatmentName(array $validated): string
    {
        return $validated['treatment_type'] === 'other'
            ? $validated['name']
            : self::TYPE_LABELS[$validated['treatment_type']];
    }

    private function authorizeOwner(Request $request, Treatment $treatment): void
    {
        abort_unless($treatment->user_id === $request->user()->id, 403);
    }
}
