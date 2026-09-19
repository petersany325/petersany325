<?php

namespace Plugins\TrainingDesk\src\Http\Controllers;

use Illuminate\Routing\Controller;
use Plugins\TrainingDesk\src\Support\TrainingCopy;

class PageController extends Controller
{
    public function hub()
    {
        $copy = TrainingCopy::get();

        return view('training-desk::hub', [
            'copy' => $copy,
            'courses' => TrainingCopy::catalog($copy),
            'tools' => TrainingCopy::tools($copy),
        ]);
    }

    public function show(string $slug)
    {
        $copy = TrainingCopy::get();
        $course = TrainingCopy::course($slug, $copy);
        if (! $course) {
            abort(404);
        }

        return view('training-desk::course', [
            'copy' => $copy,
            'course' => $course,
            'courses' => TrainingCopy::catalog($copy),
            'tools' => TrainingCopy::tools($copy),
        ]);
    }
}
