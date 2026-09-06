<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::where('user_id', $request->user()->id)
            ->withCount(['todos', 'activityLogs'])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'on_hold' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('name')->get();

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return to_route('projects.show', $project)->with('status', '暮らしの予定を追加しました。');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);
        $today = Carbon::today();

        $project->load([
            'todos' => fn ($query) => $query->orderBy('is_completed')->orderByRaw('due_date IS NULL')->orderBy('due_date'),
            'activityLogs' => fn ($query) => $query->orderByDesc('performed_on')->orderByDesc('performed_at'),
            'checkinItems' => fn ($query) => $query->with(['entries' => fn ($entryQuery) => $entryQuery->whereDate('checked_on', $today)]),
        ]);

        return view('projects.show', compact('project', 'today'));
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);
        $project->update($request->validated());

        return to_route('projects.show', $project)->with('status', '暮らしの予定を更新しました。');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);
        $project->delete();

        return to_route('projects.index')->with('status', '暮らしの予定を削除しました。関連するやることと記録は残しています。');
    }
}
