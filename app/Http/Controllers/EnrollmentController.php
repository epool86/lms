<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\TrainerChipPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    /**
     * Show checkout page for a course
     */
    public function checkout(Course $course)
    {
        // Only allow enrollment for publicly available courses
        if ($course->status !== 'open' || ($course->trainer && $course->trainer->suspended_at)) {
            abort(404);
        }

        $course->load('trainer');

        // Check if already enrolled
        if (Auth::check() && $course->isEnrolled(Auth::user())) {
            return redirect()->route('courses.show', $course)
                ->with('info', 'You are already enrolled in this course.');
        }

        // Free course + authenticated user: skip checkout entirely
        if ($course->price == 0 && Auth::check()) {
            $enrollment = Enrollment::create([
                'course_id' => $course->id,
                'user_id' => Auth::id(),
                'amount' => 0,
                'payment_method' => 'chip',
                'payment_status' => 'pending',
            ]);
            $enrollment->markAsPaid(['method' => 'free']);

            return redirect()->route('app.learn.show', $course->slug)
                ->with('success', 'You have been enrolled successfully!');
        }

        // Get trainer payment settings
        $trainer = $course->trainer;
        $trainerSettings = $trainer->trainer_settings ?? [];

        $hasChipPayment = !empty($trainerSettings['chip_brand_id']) && !empty($trainerSettings['chip_api_key']);
        $hasManualPayment = !empty($trainerSettings['manual_payment_enabled']);

        // If no payment method available and course is paid
        if ($course->price > 0 && !$hasChipPayment && !$hasManualPayment) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'This course is not available for enrollment at this time.');
        }

        return view('public.courses.checkout', [
            'course' => $course,
            'trainer' => $trainer,
            'hasChipPayment' => $hasChipPayment,
            'hasManualPayment' => $hasManualPayment,
            'bankDetails' => [
                'bank_name' => $trainerSettings['bank_name'] ?? null,
                'account_name' => $trainerSettings['bank_account_name'] ?? null,
                'account_number' => $trainerSettings['bank_account_number'] ?? null,
            ],
        ]);
    }

    /**
     * Process enrollment
     */
    public function process(Request $request, Course $course)
    {
        // Only allow enrollment for publicly available courses
        if ($course->status !== 'open' || ($course->trainer && $course->trainer->suspended_at)) {
            abort(404);
        }

        $course->load('trainer');
        $trainer = $course->trainer;
        $trainerSettings = $trainer->trainer_settings ?? [];

        // Validate request
        $rules = [
            'payment_method' => 'required|in:chip,manual,free',
        ];

        // If not logged in, require registration details
        if (!Auth::check()) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255|unique:users,email';
            $rules['phone'] = 'nullable|string|max:20';
            $rules['password'] = 'required|min:8|confirmed';
        }

        // For manual payment, require proof
        if ($request->payment_method === 'manual') {
            $rules['payment_proof'] = 'required|image|max:5120'; // 5MB max
        }

        $validated = $request->validate($rules, [
            'email.unique' => 'An account with this email already exists. Please login to continue.',
        ]);

        // Get or create user
        $user = Auth::user();

        if (!$user) {
            // Create new student account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'roles' => ['student'],
            ]);

            Auth::login($user);
        }

        // Check if already enrolled
        if ($user && $course->isEnrolled($user)) {
            return redirect()->route('courses.show', $course)
                ->with('info', 'You are already enrolled in this course.');
        }

        // Create enrollment record
        $enrollment = Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'amount' => $course->price,
            'payment_method' => $request->payment_method === 'free' ? 'chip' : $request->payment_method,
            'payment_status' => 'pending',
        ]);

        // Handle free courses
        if ($course->price == 0 || $request->payment_method === 'free') {
            $enrollment->markAsPaid(['method' => 'free']);

            return redirect()->route('enrollment.success', $enrollment->order_reference)
                ->with('success', 'You have been enrolled successfully!');
        }

        // Handle manual payment
        if ($request->payment_method === 'manual') {
            // Store payment proof
            $path = $request->file('payment_proof')->store('payment-proofs', 'public');
            $enrollment->update([
                'payment_proof' => $path,
                'payment_status' => 'pending',
            ]);

            return redirect()->route('enrollment.pending', $enrollment->order_reference)
                ->with('success', 'Your payment proof has been submitted. Please wait for verification.');
        }

        // Handle CHIP payment
        if ($request->payment_method === 'chip') {
            try {
                $chipService = new TrainerChipPaymentService($trainer);

                $checkoutUrl = $chipService->createPurchase(
                    $enrollment,
                    $user->email,
                    $user->name
                );

                return redirect()->away($checkoutUrl);
            } catch (\Exception $e) {
                $enrollment->update(['payment_status' => 'failed']);

                return redirect()->route('courses.checkout', $course)
                    ->with('error', 'Payment initialization failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('courses.show', $course)
            ->with('error', 'Invalid payment method.');
    }

    /**
     * Payment success page
     */
    public function success($orderReference)
    {
        $enrollment = Enrollment::where('order_reference', $orderReference)->firstOrFail();
        $enrollment->load(['course', 'course.trainer']);

        return view('public.courses.enrollment-success', [
            'enrollment' => $enrollment,
            'course' => $enrollment->course,
        ]);
    }

    /**
     * Payment pending page (for manual payments)
     */
    public function pending($orderReference)
    {
        $enrollment = Enrollment::where('order_reference', $orderReference)->firstOrFail();
        $enrollment->load(['course', 'course.trainer']);

        return view('public.courses.enrollment-pending', [
            'enrollment' => $enrollment,
            'course' => $enrollment->course,
        ]);
    }

    /**
     * Payment failed page
     */
    public function failed($orderReference)
    {
        $enrollment = Enrollment::where('order_reference', $orderReference)->firstOrFail();
        $enrollment->load(['course']);

        return view('public.courses.enrollment-failed', [
            'enrollment' => $enrollment,
            'course' => $enrollment->course,
        ]);
    }
}
