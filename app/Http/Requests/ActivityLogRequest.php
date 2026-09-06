<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ActivityLogRequest extends FormRequest{
 public function authorize():bool{return $this->user()!==null;}
 public function rules():array{return [
  'title'=>['required','string','max:255'],
  'project_id'=>['nullable','integer',Rule::exists('projects','id')->where(fn($query)=>$query->where('user_id',$this->user()->id)->where('uses_activity_logs',true))],
  'todo_id'=>['nullable','integer',Rule::exists('todos','id')->where('user_id',$this->user()->id)],
  'treatment_id'=>['nullable','integer',Rule::exists('treatments','id')->where('user_id',$this->user()->id)],
  'performed_on'=>['required','date'],'performed_at'=>['nullable','date_format:H:i'],'duration_minutes'=>['nullable','integer','min:1','max:1440'],
  'condition'=>['nullable',Rule::in(['good','usual','hard'])],
  'symptoms'=>['nullable','array'],'symptoms.*'=>['string',Rule::in(['fatigue','nausea','pain','fever','appetite','sleep','numbness','diarrhea','constipation','other'])],
  'memo'=>['nullable','string','max:5000'],'photos'=>['nullable','array','max:5'],'photos.*'=>['file','image','mimes:jpg,jpeg,png,webp','max:5120']
 ];}
 public function attributes():array{return ['title'=>'できたこと・記録の題名','project_id'=>'プロジェクト','todo_id'=>'関連するやること','treatment_id'=>'関連する治療','performed_on'=>'実施日','performed_at'=>'実施時刻','duration_minutes'=>'所要時間','condition'=>'体調','symptoms'=>'症状','symptoms.*'=>'症状','memo'=>'メモ','photos'=>'写真','photos.*'=>'写真'];}
}