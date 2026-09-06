<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class TodoRequest extends FormRequest {
 public function authorize(): bool { return $this->user() !== null; }
 public function rules(): array { return [
  'title'=>['required','string','max:255'],
  'project_id'=>['nullable','integer',Rule::exists('projects','id')->where(fn($query)=>$query->where('user_id',$this->user()->id)->where('uses_todos',true))],
  'due_date'=>['nullable','date'],'due_time'=>['nullable','date_format:H:i'],
  'priority'=>['required',Rule::in(['low','medium','high'])],
  'recurrence'=>['required',Rule::in(['none','daily','weekly','monthly'])],
  'remind_at'=>['nullable','date'],'memo'=>['nullable','string','max:5000'],
 ]; }
 public function attributes(): array { return ['title'=>'やること','project_id'=>'プロジェクト','due_date'=>'期限日','due_time'=>'期限時刻','priority'=>'優先度','recurrence'=>'繰り返し','remind_at'=>'リマインダー','memo'=>'メモ']; }
}

