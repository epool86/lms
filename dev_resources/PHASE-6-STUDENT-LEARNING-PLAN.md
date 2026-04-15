# Phase 6 — Student Learning: Implementation Plan

**Scope:** Post-enrollment student experience. Turn a paid `enrollment` row into a working learning library, course player, and progress tracking system.

**Out of scope (deferred):** Certificates, reviews/ratings, bookmarks, notes, discussion threads — all Phase 2 per PRD.

---

## 1. Assumptions & Current State

**Already built:**
- `enrollments` table with `user_id`, `course_id`, `status`, `payment_status`, `enrolled_at` (see [2025_11_27_143833_create_enrollments_table.php](database/migrations/2025_11_27_143833_create_enrollments_table.php))
- `Enrollment` model with `isActive()`, `scopeActive()` helpers
- `course_materials` table — **flat list, no sections** (type = `video` | `document`, has `visibility` private/public)
- CHIP payment + manual approval flow
- Streaming endpoint `PublicCourseController@streamMaterial`

**Decisions for Phase 6:**
| Question | Decision |
|---|---|
| Sections/modules | **Skip for now.** Keep materials flat. Add `order` column only. Revisit in Phase 6.5 if needed. |
| Certificates | Defer to Phase 2. |
| Bookmarks / notes | Defer. |
| Sequential unlock | **Optional toggle per course** — build the plumbing, ship with default OFF. |
| Video provider | Native HTML5 `<video>` with `video.js` (already in template). Stream via existing endpoint. |
| "Downloadable" control | Reuse `visibility`: `public` = downloadable, `private` = stream only. No new column. |

---

## 2. Database Changes

### 2.1 New migration: `create_lesson_progress_table`
```php
Schema::create('lesson_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
    $table->foreignId('course_material_id')->constrained()->onDelete('cascade');
    $table->boolean('completed')->default(false);
    $table->timestamp('completed_at')->nullable();
    $table->unsignedInteger('time_spent')->default(0);      // seconds
    $table->unsignedInteger('last_position')->default(0);   // video timestamp in seconds
    $table->timestamp('last_accessed_at')->nullable();
    $table->timestamps();

    $table->unique(['enrollment_id', 'course_material_id'], 'lp_enrollment_material_unique');
    $table->index('course_material_id');
});
```

### 2.2 Alter `enrollments` — aggregate progress columns
```php
Schema::table('enrollments', function (Blueprint $table) {
    $table->unsignedTinyInteger('progress_percentage')->default(0)->after('status');
    $table->timestamp('last_accessed_at')->nullable()->after('enrolled_at');
    $table->timestamp('completed_at')->nullable()->after('last_accessed_at');
});
```
Also extend the `status` enum to include `completed` (or switch to string — string is safer for future values).

### 2.3 Alter `course_materials` — ordering + optional preview flag
```php
Schema::table('course_materials', function (Blueprint $table) {
    $table->unsignedInteger('order')->default(0)->after('visibility');
    $table->boolean('is_preview')->default(false)->after('order'); // free preview before enrollment
});
```

### 2.4 Alter `courses` — sequential-unlock toggle
```php
Schema::table('courses', function (Blueprint $table) {
    $table->boolean('sequential_unlock')->default(false)->after('status');
});
```

---

## 3. Models

### 3.1 New: `app/Models/LessonProgress.php`
- `$fillable`: enrollment_id, course_material_id, completed, completed_at, time_spent, last_position, last_accessed_at
- `$casts`: completed_at, last_accessed_at → datetime; completed → bool
- Relations: `enrollment()`, `material()` (belongsTo `CourseMaterial`)

### 3.2 `Enrollment` — additions
- `progress()` → hasMany `LessonProgress`
- `recalculateProgress()`: counts completed materials for course, updates `progress_percentage`, sets `completed_at` + `status = completed` when 100%
- `lastAccessedMaterial()`: returns the `LessonProgress` row with most recent `last_accessed_at`, or first material if none
- `scopeInProgress`, `scopeCompleted`

### 3.3 `Course` — additions
- `orderedMaterials()` → hasMany `CourseMaterial` ordered by `order`, `id`
- Cast `sequential_unlock` as bool

### 3.4 `CourseMaterial` — additions
- `progress()` → hasMany `LessonProgress`
- `isAccessibleFor(Enrollment $enrollment)`: returns true if `is_preview`, OR course has no `sequential_unlock`, OR all prior-ordered materials are completed for this enrollment

### 3.5 `User` — additions
- `enrollments()` → hasMany `Enrollment`
- `activeEnrollments()` → filtered scope

---

## 4. Middleware & Authorization

### 4.1 New middleware: `EnsureEnrolled`
- Registered as `enrolled` in `bootstrap/app.php`
- Resolves route's `{course}` param, verifies `Enrollment::active()->where('user_id', auth()->id())->where('course_id', $course->id)->exists()`
- Does **NOT** check `expires_at` (deferred — see §11)
- Aborts 403 if not enrolled; redirects guests to login
- Trainers are NOT granted access to their own course player here — they use the trainer UI

### 4.2 Policy: `CourseMaterialPolicy`
- `view(User $user, CourseMaterial $material)` — true if: material `is_preview = true`, OR user has active enrollment AND (not `sequential_unlock` OR prior materials completed)
- Trainer-ownership bypass is NOT included (per decision §11.3)

---

## 5. Routes (`routes/web.php`)

Add inside `auth, verified` group:

```php
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
```

---

## 6. Controllers

### 6.1 New: `app/Http/Controllers/LearningController.php`

| Method | Responsibility |
|---|---|
| `index()` | List authenticated user's active enrollments. Query param `tab=all\|in_progress\|completed`. Eager-load course + trainer + cover. Returns `learning.index` view. |
| `purchases()` | Full history including pending/failed. Returns `learning.purchases`. |
| `show(Course $course)` | Redirect to last-accessed material OR first material. |
| `material(Course $course, CourseMaterial $material)` | Render player. Loads: ordered materials list, current material, progress map, prev/next IDs. Authorizes via policy. Touches `last_accessed_at` on enrollment + progress row. |
| `stream(Course $course, CourseMaterial $material)` | Auth-gated file stream. Delegates to existing streaming logic (extract shared service). |

### 6.2 New: `app/Http/Controllers/LearningProgressController.php`

| Method | Responsibility |
|---|---|
| `update()` | AJAX. Accepts `last_position` (int), `time_spent_delta` (int). Upserts `lesson_progress`. Validates ownership. Returns JSON `{ok: true}`. Throttled (e.g. once per 10s per material). |
| `complete()` | AJAX. Marks material completed, sets `completed_at`, calls `Enrollment::recalculateProgress()`. Returns updated progress %. |

### 6.3 Dashboard update
- Inject real counts into [dashboard.blade.php](resources/views/dashboard.blade.php) via `DashboardController` (new) OR closure in route:
  - enrolled count, in-progress count, completed count
  - "Continue learning" block: 3 most recent in-progress enrollments

---

## 7. Views (Blade)

Base on Dreams LMS template pages: `course-lesson.html`, `student-courses.html`, `student-purchase-history.html`.

### 7.1 `resources/views/learning/index.blade.php`
- Header with tabs: All / In Progress / Completed (query-param driven)
- Grid of course cards:
  - Cover image, title, trainer name
  - Progress bar (`progress_percentage`)
  - "Continue Learning" button → `learn.show`
  - Badge for Completed
- Empty state with CTA to browse

### 7.2 `resources/views/learning/player.blade.php`
Three-column layout:
- **Left sidebar (300px):** Materials list
  - Grouped by none (flat) for Phase 6
  - Each row: icon (video/doc), title, duration/size, status icon (✓ completed, ● in-progress, 🔒 locked)
  - Active material highlighted
- **Main area:**
  - Video: `<video>` with `video.js` skin, `src` = `learn.stream` route
  - PDF/doc: `<iframe>` or download button depending on visibility
  - Title + description below player
  - **Actions bar:** Previous | Mark as Complete | Next
- **Footer or collapsible:** course title + overall progress bar

### 7.3 `resources/views/learning/purchases.blade.php`
Table: Order Ref | Course | Amount | Method | Status | Date | Receipt

### 7.4 Sidebar nav update
- [layouts/partials/sidebar.blade.php](resources/views/layouts/partials/sidebar.blade.php): add "My Courses", "Purchase History" entries for student role

---

## 8. Frontend JS — Progress Tracking

New file: `public/assets/js/learning-player.js`

**Video progress:**
- On `timeupdate`: throttle to 10s intervals → POST `last_position`, `time_spent_delta`
- On ≥90% watched OR `ended`: auto-call `complete` endpoint
- On load: seek to saved `last_position`

**Doc/PDF progress:**
- "Mark as Complete" button → POST `complete` endpoint
- Opening / downloading a document must NOT auto-complete it (explicit action only)

**Client state:**
- After complete: update sidebar row checkmark, overall progress bar, enable Next button
- CSRF via `<meta name="csrf-token">`

---

## 9. Build Order (Tasks)

Recommended commit-by-commit sequence:

1. **Migrations + models** — `lesson_progress` table, enrollment/course/material column additions, `LessonProgress` model, relations.
2. **Middleware + policy** — `EnsureEnrolled`, `CourseMaterialPolicy`.
3. **Free-course enrollment shortcut** — update `EnrollmentController` so `price = 0` skips CHIP and creates a paid+active enrollment immediately, redirecting to `learn.show`.
4. **Learning library page** — `LearningController@index`, `learning/index.blade.php`, sidebar link. No player yet.
5. **Course player (read-only)** — `show` + `material` methods, `learning/player.blade.php`, prev/next nav. No progress tracking yet.
6. **Progress tracking** — `LearningProgressController`, AJAX endpoints, `learning-player.js`, `Enrollment::recalculateProgress()`.
7. **Dashboard widgets** — real counts + "Continue learning".
8. **Purchase history page**.
9. **Sequential unlock** — enable toggle on course edit form, enforce in policy + sidebar UI.
10. **Polish** — empty states, error pages, 403 view for non-enrolled access, mobile responsiveness.

Each step should be independently testable and committable.

---

## 10. Testing Checklist

- [ ] Non-enrolled user cannot access `/learn/{course}` (redirects / 403)
- [ ] Trainer accessing own course via `/learn/{course}` is blocked (by design)
- [ ] Video resume works after page reload (and across devices — last write wins)
- [ ] Completing last material flips enrollment to `completed` with `completed_at` set
- [ ] Progress % updates on dashboard after completion
- [ ] Sequential unlock blocks jumping ahead when enabled
- [ ] Preview materials (`is_preview = true`) accessible to non-enrolled visitors
- [ ] Free course (price = 0) enroll button skips CHIP and lands user in player immediately
- [ ] Opening a PDF/doc does NOT auto-mark complete; only the explicit button does
- [ ] Guest enrollments (`user_id` null) do NOT appear in anyone's library
- [ ] Expiry is NOT enforced in Phase 6 — an enrollment with `expires_at` in the past still grants access

---

## 11. Resolved Decisions

1. **Enrollment expiry:** Ignore `expires_at` for Phase 6. Every `active` + `paid` enrollment = lifetime access. Revisit if/when time-limited courses are introduced.
2. **Free courses (price = 0):** Skip the payment flow entirely. On "Enroll" click, create an enrollment with `payment_status = paid`, `status = active`, `enrolled_at = now()`, and redirect straight into the player.
3. **Trainer preview:** Not supported. Trainers manage materials via the existing trainer UI; they cannot access the student `/learn/{course}` player on their own courses.
4. **Document progress:** Always require explicit "Mark as Complete" click. Opening/downloading a doc does NOT auto-complete it.
5. **Multi-device resume:** Single `last_position` per material per enrollment. Last write wins across devices — acceptable.

---

*Created: 2026-04-15. Tracks implementation against PRD §FR-4.3 and §FR-4.4.*
