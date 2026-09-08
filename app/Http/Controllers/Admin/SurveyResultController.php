<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyAssignment;
use Illuminate\Contracts\View\View;

class SurveyResultController extends Controller
{
    public function show(SurveyAssignment $surveyAssignment): View
    {
        $surveyAssignment->load(['user', 'definition.questions', 'answers']);

        return view('admin.surveys.results.show', compact('surveyAssignment'));
    }
}
