<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ActivityLogPhotoController;
use App\Http\Controllers\Admin\SurveyAssignmentController as AdminSurveyAssignmentController;
use App\Http\Controllers\Admin\SurveyDefinitionController as AdminSurveyDefinitionController;
use App\Http\Controllers\Admin\SurveyExportController as AdminSurveyExportController;
use App\Http\Controllers\Admin\SurveyQuestionController as AdminSurveyQuestionController;
use App\Http\Controllers\Admin\SurveyResultController as AdminSurveyResultController;
use App\Http\Controllers\Admin\UsageDashboardController as AdminUsageDashboardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CareController;
use App\Http\Controllers\CheckinEntryController;
use App\Http\Controllers\CheckinItemController;
use App\Http\Controllers\LifeGoalController;
use App\Http\Controllers\LifeGoalEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SurveyResponseController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UsageDashboardController;
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
    Route::get('/usage', UsageDashboardController::class)->name('usage.index');
    Route::get('/surveys/{surveyAssignment}', [SurveyResponseController::class, 'edit'])->name('surveys.edit');
    Route::put('/surveys/{surveyAssignment}', [SurveyResponseController::class, 'update'])->name('surveys.update');
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

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usage', AdminUsageDashboardController::class)->name('usage.index');
    Route::get('/surveys', [AdminSurveyDefinitionController::class, 'index'])->name('surveys.index');
    Route::post('/surveys', [AdminSurveyDefinitionController::class, 'store'])->name('surveys.store');
    Route::put('/surveys/{surveyDefinition}', [AdminSurveyDefinitionController::class, 'update'])->name('surveys.update');
    Route::post('/surveys/{surveyDefinition}/assignments', [AdminSurveyAssignmentController::class, 'store'])->name('survey-assignments.store');
    Route::post('/surveys/{surveyDefinition}/questions', [AdminSurveyQuestionController::class, 'store'])->name('survey-questions.store');
    Route::put('/survey-questions/{surveyQuestion}', [AdminSurveyQuestionController::class, 'update'])->name('survey-questions.update');
    Route::delete('/survey-questions/{surveyQuestion}', [AdminSurveyQuestionController::class, 'destroy'])->name('survey-questions.destroy');
    Route::get('/survey-assignments/{surveyAssignment}', [AdminSurveyResultController::class, 'show'])->name('survey-results.show');
    Route::get('/surveys/{surveyDefinition}/export', AdminSurveyExportController::class)->name('surveys.export');
});

require __DIR__.'/auth.php';
