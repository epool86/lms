<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseMaterialController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentWebhookController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningProgressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCourseController;
use App\Http\Controllers\PublicTrainerController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TrainerEnrollmentController;
use App\Http\Controllers\TrainerSettingController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TrainerController as AdminTrainerController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public course routes (no auth required)
Route::get('/courses', [PublicCourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [PublicCourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{course}/cover/{type?}', [PublicCourseController::class, 'coverImage'])->name('courses.cover');
Route::get('/courses/{course}/materials/{material}', [PublicCourseController::class, 'showMaterial'])->name('courses.materials.show');
Route::get('/courses/{course}/materials/{material}/stream', [PublicCourseController::class, 'streamMaterial'])->name('courses.materials.stream');
Route::get('/trainers', [PublicTrainerController::class, 'index'])->name('trainers.index');

// Enrollment routes (public - for course purchase)
Route::get('/courses/{course:slug}/checkout', [EnrollmentController::class, 'checkout'])->name('courses.checkout');
Route::post('/courses/{course:slug}/enroll', [EnrollmentController::class, 'process'])->name('enrollment.process');
Route::get('/enrollment/{orderReference}/success', [EnrollmentController::class, 'success'])->name('enrollment.success');
Route::get('/enrollment/{orderReference}/pending', [EnrollmentController::class, 'pending'])->name('enrollment.pending');
Route::get('/enrollment/{orderReference}/failed', [EnrollmentController::class, 'failed'])->name('enrollment.failed');

// CHIP enrollment callback and webhook routes
Route::get('/enrollment/{orderReference}/callback/{status}', [EnrollmentWebhookController::class, 'callback'])->name('enrollment.chip.callback');
Route::post('/webhook/enrollment/chip', [EnrollmentWebhookController::class, 'webhook'])->name('enrollment.chip.webhook');

// Webhook route (no CSRF protection needed)
Route::post('/webhook/chip', [WebhookController::class, 'handleChipWebhook'])->name('webhook.chip');

// Member area routes (all authenticated users) - /app prefix
Route::middleware(['auth', 'verified', 'not.suspended'])->prefix('app')->name('app.')->group(function () {
    // Dashboard
    Route::get('/', function () {
        $user = auth()->user();

        if (in_array('admin', $user->roles ?? [])) {
            return redirect()->route('app.admin.dashboard');
        }

        $enrolledCount = $user->enrollments()->where('payment_status', 'paid')->whereIn('status', ['active', 'completed'])->count();
        $inProgressCount = $user->enrollments()->inProgress()->count();
        $completedCount = $user->enrollments()->completed()->count();

        $continueLearning = $user->enrollments()
            ->with('course.trainer')
            ->inProgress()
            ->orderByDesc('last_accessed_at')
            ->orderByDesc('enrolled_at')
            ->limit(3)
            ->get();

        return view('dashboard', compact('enrolledCount', 'inProgressCount', 'completedCount', 'continueLearning'));
    })->name('dashboard');

    // Student learning library
    Route::get('/my-courses', [LearningController::class, 'index'])->name('my-courses.index');
    Route::get('/my-purchases', [LearningController::class, 'purchases'])->name('my-purchases.index');

    // Course player (enrollment-gated)
    Route::middleware('enrolled')->group(function () {
        Route::get('/learn/{course:slug}', [LearningController::class, 'show'])->name('learn.show');
        Route::get('/learn/{course:slug}/materials/{material}', [LearningController::class, 'material'])->name('learn.material');
        Route::get('/learn/{course:slug}/materials/{material}/stream', [LearningController::class, 'stream'])->name('learn.stream');

        // Progress tracking (AJAX)
        Route::post('/learn/{course:slug}/materials/{material}/progress', [LearningProgressController::class, 'update'])->name('learn.progress.update');
        Route::post('/learn/{course:slug}/materials/{material}/complete', [LearningProgressController::class, 'complete'])->name('learn.progress.complete');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Plan selection routes
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans/select', [PlanController::class, 'select'])->name('plans.select');

    // Payment redirect routes
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

    // Course management routes (trainers)
    Route::resource('courses', CourseController::class);
    Route::get('/courses/{course}/cover/{type?}', [CourseController::class, 'coverImage'])->name('courses.cover');

    // Course materials routes
    Route::get('/courses/{course}/materials', [CourseMaterialController::class, 'index'])->name('course-materials.index');
    Route::get('/courses/{course}/materials/create', [CourseMaterialController::class, 'create'])->name('course-materials.create');
    Route::post('/courses/{course}/materials', [CourseMaterialController::class, 'store'])->name('course-materials.store');
    Route::get('/courses/{course}/materials/{material}', [CourseMaterialController::class, 'show'])->name('course-materials.show');
    Route::get('/courses/{course}/materials/{material}/edit', [CourseMaterialController::class, 'edit'])->name('course-materials.edit');
    Route::put('/courses/{course}/materials/{material}', [CourseMaterialController::class, 'update'])->name('course-materials.update');
    Route::delete('/courses/{course}/materials/{material}', [CourseMaterialController::class, 'destroy'])->name('course-materials.destroy');
    Route::get('/courses/{course}/materials/{material}/download', [CourseMaterialController::class, 'download'])->name('course-materials.download');
    Route::get('/courses/{course}/materials/{material}/stream', [CourseMaterialController::class, 'stream'])->name('course-materials.stream');

    // Trainer routes - /app/trainer prefix
    Route::prefix('trainer')->name('trainer.')->group(function () {
        // Trainer settings
        Route::get('/settings', [TrainerSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [TrainerSettingController::class, 'update'])->name('settings.update');

        // Trainer enrollments management
        Route::get('/enrollments', [TrainerEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('/enrollments/{enrollment}', [TrainerEnrollmentController::class, 'show'])->name('enrollments.show');
        Route::patch('/enrollments/{enrollment}/approve', [TrainerEnrollmentController::class, 'approve'])->name('enrollments.approve');
        Route::patch('/enrollments/{enrollment}/reject', [TrainerEnrollmentController::class, 'reject'])->name('enrollments.reject');
    });

    // Admin routes (admin only) - /app/admin prefix
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/trainers', [AdminTrainerController::class, 'index'])->name('trainers.index');
        Route::get('/trainers/{user}', [AdminTrainerController::class, 'show'])->name('trainers.show');
        Route::get('/trainers/{user}/edit', [AdminTrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{user}', [AdminTrainerController::class, 'update'])->name('trainers.update');
        Route::post('/trainers/{user}/suspend', [AdminTrainerController::class, 'suspend'])->name('trainers.suspend');
        Route::post('/trainers/{user}/restore', [AdminTrainerController::class, 'restore'])->name('trainers.restore');
        Route::delete('/trainers/{user}', [AdminTrainerController::class, 'destroy'])->name('trainers.destroy');

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{user}', [AdminStudentController::class, 'show'])->name('students.show');
        Route::get('/students/{user}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{user}', [AdminStudentController::class, 'update'])->name('students.update');
        Route::post('/students/{user}/suspend', [AdminStudentController::class, 'suspend'])->name('students.suspend');
        Route::post('/students/{user}/restore', [AdminStudentController::class, 'restore'])->name('students.restore');
        Route::delete('/students/{user}', [AdminStudentController::class, 'destroy'])->name('students.destroy');

        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [AdminCourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::post('/courses/{course}/publish', [AdminCourseController::class, 'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish', [AdminCourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('courses.destroy');

        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{section}/export/{format}', [AdminReportController::class, 'export'])->name('reports.export');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/pricing', [SettingController::class, 'updatePricing'])->name('settings.pricing.update');
    });
});

// Legacy redirect for old /dashboard URL
Route::get('/dashboard', function () {
    return redirect()->route('app.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
