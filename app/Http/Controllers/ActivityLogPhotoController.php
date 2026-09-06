<?php
namespace App\Http\Controllers;
use App\Models\ActivityLogPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
class ActivityLogPhotoController extends Controller{public function destroy(ActivityLogPhoto $photo):RedirectResponse{Gate::authorize('delete',$photo);Storage::disk($photo->disk)->delete($photo->path);$photo->delete();return back()->with('status','写真を削除しました。');}}
