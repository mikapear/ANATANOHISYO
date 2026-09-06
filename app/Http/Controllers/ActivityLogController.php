<?php
namespace App\Http\Controllers;
use App\Http\Requests\ActivityLogRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Todo;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
class ActivityLogController extends Controller{
 public function index(Request $request):View{$projects=Project::where('user_id',$request->user()->id)->where('uses_activity_logs',true)->orderBy('name')->get();$projectId=$projects->firstWhere('id',(int)$request->integer('project_id'))?->id;$query=ActivityLog::with(['project','todo','treatment','photos'])->where('user_id',$request->user()->id);if($projectId)$query->where('project_id',$projectId);$logs=$query->orderByDesc('performed_on')->orderByDesc('performed_at')->latest('id')->get();return view('activity-logs.index',compact('logs','projects','projectId'));}
 public function create(Request $request):View{return view('activity-logs.create',$this->options($request));}
 public function store(ActivityLogRequest $request):RedirectResponse{$log=$request->user()->activityLogs()->create($request->safe()->except('photos'));$this->storePhotos($log,$request);return to_route('activity-logs.show',$log)->with('status','できたことを記録しました。');}
 public function show(ActivityLog $activityLog):View{Gate::authorize('view',$activityLog);$activityLog->load(['project','todo','treatment','photos'=>fn($q)=>$q->orderBy('sort_order')]);return view('activity-logs.show',compact('activityLog'));}
 public function edit(Request $request,ActivityLog $activityLog):View{Gate::authorize('update',$activityLog);$activityLog->load('photos');return view('activity-logs.edit',['activityLog'=>$activityLog,...$this->options($request)]);}
 public function update(ActivityLogRequest $request,ActivityLog $activityLog):RedirectResponse{Gate::authorize('update',$activityLog);$newPhotos=$request->file('photos',[]);if($activityLog->photos()->count()+count($newPhotos)>5)return back()->withErrors(['photos'=>'写真は既存分を含めて5枚までです。'])->withInput();$activityLog->update($request->safe()->except('photos'));$this->storePhotos($activityLog,$request);return to_route('activity-logs.show',$activityLog)->with('status','活動記録を更新しました。');}
 public function destroy(ActivityLog $activityLog):RedirectResponse{Gate::authorize('delete',$activityLog);$activityLog->load('photos');foreach($activityLog->photos as $photo)Storage::disk($photo->disk)->delete($photo->path);$activityLog->delete();return to_route('activity-logs.index')->with('status','活動記録を削除しました。');}
 private function options(Request $request):array{return ['projects'=>Project::where('user_id',$request->user()->id)->where('uses_activity_logs',true)->orderBy('name')->get(),'todos'=>Todo::where('user_id',$request->user()->id)->whereHas('project',fn($q)=>$q->where('uses_activity_logs',true))->orWhere(fn($q)=>$q->where('user_id',$request->user()->id)->whereNull('project_id'))->orderByDesc('created_at')->get(),'treatments'=>Treatment::where('user_id',$request->user()->id)->orderByDesc('scheduled_on')->orderByDesc('scheduled_at')->get()];}
 private function storePhotos(ActivityLog $log,ActivityLogRequest $request):void{$order=(int)$log->photos()->max('sort_order');foreach($request->file('photos',[]) as $file){$path=$file->store("activity-logs/{$log->id}",'public');$log->photos()->create(['disk'=>'public','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),'sort_order'=>++$order]);}}
}