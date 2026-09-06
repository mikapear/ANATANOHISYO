<x-site-app title="暮らしの予定を編集 | ANATANOHISHO"><div class="mx-auto max-w-2xl"><h1 class="text-2xl font-bold text-indigo-950">暮らしの予定を編集する</h1><form method="POST" action="{{ route('projects.update',$project) }}" class="form-panel">@method('PUT') @include('projects._form',['submitLabel'=>'更新する','cancelUrl'=>route('projects.show',$project)])</form></div></x-site-app>

