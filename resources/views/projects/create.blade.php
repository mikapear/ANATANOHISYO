<x-site-app title="暮らしの予定を追加 | ANATANOHISHO"><div class="mx-auto max-w-2xl"><h1 class="text-2xl font-bold text-indigo-950">暮らしの予定を追加する</h1><form method="POST" action="{{ route('projects.store') }}" class="form-panel">@include('projects._form',['project'=>null,'submitLabel'=>'追加する','cancelUrl'=>route('projects.index')])</form></div></x-site-app>

