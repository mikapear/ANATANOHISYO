@php
$conditions=['good'=>['よい','😊'],'usual'=>['ふつう','🙂'],'hard'=>['つらい','😣']];
$symptomLabels=['fatigue'=>'だるさ','nausea'=>'吐き気','pain'=>'痛み','fever'=>'発熱','appetite'=>'食欲の変化','sleep'=>'眠りの変化','numbness'=>'しびれ','diarrhea'=>'下痢','constipation'=>'便秘','other'=>'その他'];
$selectedSymptoms=old('symptoms',$activityLog?->symptoms??[]);
@endphp
@csrf
@if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4"><x-input-error :messages="$errors->all()"/></div>@endif
<div><label class="form-label">できたこと・記録の題名 <span>必須</span></label><input class="form-input" name="title" value="{{ old('title',$activityLog?->title??request('title')) }}" maxlength="255" placeholder="例：抗がん剤治療1日目、朝の散歩" required autofocus></div>
<div class="grid gap-5 sm:grid-cols-2">
<div><label class="form-label">暮らしの予定</label><select class="form-input" name="project_id"><option value="">指定しない</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string)old('project_id',$activityLog?->project_id??request('project_id'))===(string)$project->id)>{{ $project->name }}</option>@endforeach</select></div>
<div><label class="form-label">関連するやること</label><select class="form-input" name="todo_id"><option value="">指定しない</option>@foreach($todos as $todo)<option value="{{ $todo->id }}" @selected((string)old('todo_id',$activityLog?->todo_id??request('todo_id'))===(string)$todo->id)>{{ $todo->title }}</option>@endforeach</select></div>
</div>
<div>
<label class="form-label">関連する治療</label>
<select class="form-input" name="treatment_id">
<option value="">治療後の記録ではない</option>
@foreach($treatments as $treatment)<option value="{{ $treatment->id }}" @selected((string)old('treatment_id',$activityLog?->treatment_id??request('treatment_id'))===(string)$treatment->id)>{{ $treatment->scheduled_on->format('Y年n月j日') }}　{{ $treatment->name }}@if($treatment->cycle_number)（第{{ $treatment->cycle_number }}クール）@endif</option>@endforeach
</select>
<p class="form-help">治療後の体調や症状を残すときだけ選びます。</p>
</div>
<div class="grid gap-5 sm:grid-cols-3">
<div><label class="form-label">日付 <span>必須</span></label><input class="form-input" type="date" name="performed_on" value="{{ old('performed_on',$activityLog?->performed_on?->format('Y-m-d')??request('performed_on',today()->format('Y-m-d'))) }}" required></div>
<div><label class="form-label">時刻</label><input class="form-input" type="time" name="performed_at" value="{{ old('performed_at',$activityLog?->performed_at?substr($activityLog->performed_at,0,5):'') }}"></div>
<div><label class="form-label">所要時間（分）</label><input class="form-input" type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes',$activityLog?->duration_minutes) }}"></div>
</div>
<section class="condition-panel">
<p class="form-label">今日の体調</p><p class="form-help">記録したいときだけ選べます。</p>
<div class="mt-3 grid grid-cols-3 gap-2">@foreach($conditions as $value=>[$label,$icon])<label class="condition-option"><input type="radio" name="condition" value="{{ $value }}" @checked(old('condition',$activityLog?->condition)===$value)><span aria-hidden="true">{{ $icon }}</span><strong>{{ $label }}</strong></label>@endforeach</div>
<div class="mt-5"><p class="form-label">気になる症状</p><div class="mt-3 flex flex-wrap gap-2">@foreach($symptomLabels as $value=>$label)<label class="symptom-option"><input type="checkbox" name="symptoms[]" value="{{ $value }}" @checked(in_array($value,$selectedSymptoms,true))><span>{{ $label }}</span></label>@endforeach</div></div>
</section>
<div><label class="form-label">メモ</label><textarea class="form-input min-h-32" name="memo" maxlength="5000" placeholder="体調の変化や、残しておきたいことを自由に書けます。">{{ old('memo',$activityLog?->memo) }}</textarea></div>
<div><label class="form-label">写真</label><input class="form-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><p class="form-help">JPEG・PNG・WebP、1枚5MBまで、合計5枚まで。</p></div>
<div class="flex gap-3"><button class="primary-action">{{ $submitLabel }}</button><a href="{{ $cancelUrl }}" class="secondary-action">キャンセル</a></div>
