<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Enrollment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $course = $request->route('course');

        if (! $course instanceof Course) {
            abort(404);
        }

        $enrolled = Enrollment::active()
            ->where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->exists();

        if (! $enrolled) {
            abort(403, 'You are not enrolled in this course.');
        }

        return $next($request);
    }
}
