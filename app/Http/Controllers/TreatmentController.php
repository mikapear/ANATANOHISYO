<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $treatments = Treatment::where('user_id', $user->id)
            ->with('project:id,name')
            ->orderBy('scheduled_on')
            ->orderBy('scheduled_at')
            ->get();
        $projects = Project::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('treatments.index', compact('treatments', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $request->user()->treatments()->create($validated + ['status' => 'scheduled']);

        return back()->with('status', '治療予定を登録しました。');
    }

    public function update(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $treatment->update($this->validated($request));

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

    public function destroy(Request $request, Treatment $treatment): RedirectResponse
    {
        $this->authorizeOwner($request, $treatment);
        $treatment->delete();

        return back()->with('status', '治療予定を削除しました。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'treatment_type' => ['required', Rule::in(['chemotherapy', 'infusion', 'injection', 'radiation', 'procedure', 'other'])],
            'scheduled_on' => ['required', 'date'],
            'scheduled_at' => ['nullable', 'date_format:H:i'],
            'cycle_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'hospital' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'postponed', 'cancelled', 'changed'])],
        ]);
    }

    private function authorizeOwner(Request $request, Treatment $treatment): void
    {
        abort_unless($treatment->user_id === $request->user()->id, 403);
    }
}