<?php

namespace Plugins\TrainingDesk\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\TrainingDesk\src\Support\TrainingCopy;

class TrainingController extends Controller
{
    public function edit()
    {
        return view('training-desk::admin', [
            'copy' => TrainingCopy::get(),
            'prefixes' => TrainingCopy::prefixes(),
        ]);
    }

    public function save(Request $request)
    {
        TrainingCopy::save($request->all());

        return redirect()
            ->to(url('/admin/training-page'))
            ->with('success', 'متن و هزینه دوره‌های آموزش ذخیره شد.');
    }
}
