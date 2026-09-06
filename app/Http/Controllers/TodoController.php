<?php
namespace App\Http\Controllers;
use App\Http\Requests\TodoRequest;
use App\Models\Project;
use App\Models\Todo;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
class TodoController extends Controller {
 public function index(Request $request): View { $projects=Project::where('user_id',$request->user()->id)->where('uses_todos',true)->orderBy('name')->get(); $projectId=$projects->firstWhere('id',(int)$request->integer('project_id'))?->id; $query=Todo::with('project')->where('user_id',$request->user()->id); if($projectId)$query->where('project_id',$projectId); $todos=$query->orderBy('is_completed')->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderByRaw('due_time IS NULL')->orderBy('due_time')->latest('id')->get(); return view('todos.index',compact('todos','projects','projectId')); }
 public function create(Request $request): View { return view('todos.create',['projects'=>$this->projects($request)]); }
 public function store(TodoRequest $request): RedirectResponse { $request->user()->todos()->create($request->validated()); return to_route('todos.index')->with('status','やることを追加しました。'); }
 public function edit(Request $request,Todo $todo): View { Gate::authorize('update',$todo); return view('todos.edit',['todo'=>$todo,'projects'=>$this->projects($request)]); }
 public function update(TodoRequest $request,Todo $todo): RedirectResponse { Gate::authorize('update',$todo); $todo->update($request->validated()); return to_route('todos.index')->with('status','やることを更新しました。'); }
 public function destroy(Todo $todo): RedirectResponse { Gate::authorize('delete',$todo); $todo->delete(); return to_route('todos.index')->with('status','やることを削除しました。'); }
 public function complete(Todo $todo): RedirectResponse { Gate::authorize('update',$todo); DB::transaction(function()use($todo){$todo=Todo::lockForUpdate()->findOrFail($todo->id); if($todo->is_completed)return; $todo->update(['is_completed'=>true,'completed_at'=>now()]); if($todo->recurrence!=='none'&&!$todo->recurrenceChild()->exists())$this->createNext($todo);}); return back()->with('status','花丸です。今日もひとつ進みましたね。'); }
 public function reopen(Todo $todo): RedirectResponse { Gate::authorize('update',$todo); $todo->update(['is_completed'=>false,'completed_at'=>null]); return back()->with('status','未完了に戻しました。'); }
 private function projects(Request $request){return Project::where('user_id',$request->user()->id)->where('status','active')->where('uses_todos',true)->orderBy('name')->get();}
 private function createNext(Todo $todo): void { $unit=match($todo->recurrence){'daily'=>'day','weekly'=>'week','monthly'=>'month'}; $date=CarbonImmutable::instance($todo->due_date??$todo->completed_at)->add(1,$unit); $nextReminder=$todo->remind_at?CarbonImmutable::instance($todo->remind_at)->add(1,$unit):null; Todo::create(['user_id'=>$todo->user_id,'project_id'=>$todo->project_id,'recurrence_parent_id'=>$todo->id,'title'=>$todo->title,'memo'=>$todo->memo,'due_date'=>$date,'due_time'=>$todo->due_time,'priority'=>$todo->priority,'recurrence'=>$todo->recurrence,'remind_at'=>$nextReminder]); }
}




