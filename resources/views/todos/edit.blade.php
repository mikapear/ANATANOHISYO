<x-site-app title="やることを編集 | ANATANOHISHO"><div class="mx-auto max-w-2xl"><h1 class="text-2xl font-bold text-indigo-950">やることを編集する</h1><form method="POST" action="{{ route('todos.update',$todo) }}" class="form-panel">@method('PUT') @include('todos._form',['submitLabel'=>'更新する'])</form></div></x-site-app>

