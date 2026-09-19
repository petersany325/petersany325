<?php

namespace Plugins\TrainingDesk\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\TrainingDesk\src\Support\TrainingCopy;
use Symfony\Component\HttpFoundation\Response;

class ServeTrainingPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if ($path !== 'training' && ! str_starts_with($path, 'training/')) {
            return $next($request);
        }

        try {
            $copy = TrainingCopy::get();
            if ($path === 'training') {
                return response()->view('training-desk::hub', [
                    'copy' => $copy,
                    'courses' => TrainingCopy::catalog($copy),
                    'tools' => TrainingCopy::tools($copy),
                ]);
            }

            $slug = trim(substr($path, strlen('training/')), '/');
            $course = TrainingCopy::course($slug, $copy);
            if ($course) {
                return response()->view('training-desk::course', [
                    'copy' => $copy,
                    'course' => $course,
                    'courses' => TrainingCopy::catalog($copy),
                    'tools' => TrainingCopy::tools($copy),
                ]);
            }
        } catch (\Throwable) {
        }

        return $next($request);
    }
}
