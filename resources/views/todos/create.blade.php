<x-site-app title="やることを追加 | ANATANOHISHO"><div class="mx-auto max-w-2xl"><h1 class="text-2xl font-bold text-indigo-950">やることを追加する</h1><form method="POST" action="{{ route('todos.store') }}" class="form-panel">@include('todos._form',['todo'=>null,'submitLabel'=>'追加する'])</form></div></x-site-app>

