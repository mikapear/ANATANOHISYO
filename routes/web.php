<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ActivityLogPhotoController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CareController;
use App\Http\Controllers\CheckinEntryController;
use App\Http\Controllers\CheckinItemController;
use App\Http\Controllers\LifeGoalController;
use App\Http\Controllers\LifeGoalEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('calendar.index')
        : view('welcome');
});

Route::get('/dashboard', [TodayController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/care', [CareController::class, 'index'])->name('care.index');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('treatments/{treatment}/status', [TreatmentController::class, 'updateStatus'])->name('treatments.status');
    Route::patch('treatments/{treatment}/summary', [TreatmentController::class, 'updateSummary'])->name('treatments.summary');
    Route::resource('treatments', TreatmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/goals/manage/{category}', [LifeGoalController::class, 'manage'])->name('goals.manage');
    Route::resource('goals', LifeGoalController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::put('/goals/{goal}/entry', [LifeGoalEntryController::class, 'update'])->name('goal-entries.update');
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/checkin-items', [CheckinItemController::class, 'store'])->name('checkin-items.store');
    Route::patch('/checkin-items/{checkinItem}', [CheckinItemController::class, 'update'])->name('checkin-items.update');
    Route::delete('/checkin-items/{checkinItem}', [CheckinItemController::class, 'destroy'])->name('checkin-items.destroy');
    Route::put('/checkin-items/{checkinItem}/entry', [CheckinEntryController::class, 'update'])->name('checkin-entries.update');
    Route::resource('activity-logs', ActivityLogController::class);
    Route::delete('/activity-log-photos/{photo}', [ActivityLogPhotoController::class, 'destroy'])->name('activity-log-photos.destroy');
    Route::resource('todos', TodoController::class)->except('show');
    Route::patch('/todos/{todo}/complete', [TodoController::class, 'complete'])->name('todos.complete');
    Route::patch('/todos/{todo}/reopen', [TodoController::class, 'reopen'])->name('todos.reopen');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
