# Phase 7 — Admin Panel: Implementation Plan

**Scope:** Give platform admins oversight and control: analytics dashboard, user management (trainers + students), course oversight, and basic reporting. Per PRD §FR-2 and §Admin User Stories.

**Out of scope (deferred):** Email notifications to suspended users, audit log / activity feed, bulk CSV import/export, advanced reporting with custom date ranges, impersonation (login-as-user).

---

## 1. Current State

**Already built:**
- `admin` middleware → [AdminMiddleware](app/Http/Middleware/AdminMiddleware.php)
- Route group `/app/admin/*` with admin middleware
- Settings page (site_name, pricing) — `app.admin.settings.*`
- Sidebar shows placeholder links for "Manage Trainer / Courses / Student"

**Gaps:**
- No admin dashboard (admin lands on the same user dashboard as students/trainers)
- No trainer management UI
- No student management UI
- No course oversight UI (admin cannot see/edit other trainers' courses)
- No reporting page
- No admin-specific layout / distinct navigation

---

## 2. Features & Deliverables

### 2.1 Admin Dashboard
- **Route:** `app.admin.dashboard` → `/app/admin`
- **Widgets:**
  - Stat cards: Total Users (broken down by role), Total Courses (published / draft), Total Enrollments (paid / pending), Total Revenue (sum of paid enrollments.amount)
  - Recent Enrollments table (last 10)
  - Recent Users table (last 10)
  - Revenue chart (last 30 days, simple bar chart using chart.js from template)
- **Routing change:** If logged-in user has `admin` role, redirect `/app` to `/app/admin` (or show admin dashboard instead of student dashboard). Keeps one entry point.

### 2.2 Trainer Management
- **Routes:**
  - `GET /app/admin/trainers` — list
  - `GET /app/admin/trainers/{user}` — show
  - `GET /app/admin/trainers/{user}/edit` — edit
  - `PUT /app/admin/trainers/{user}` — update
  - `POST /app/admin/trainers/{user}/suspend` — suspend (soft)
  - `POST /app/admin/trainers/{user}/restore` — restore
  - `DELETE /app/admin/trainers/{user}` — delete (hard or soft — see §6)
- **List view:** name, email, plan, # courses, # students (sum enrollments on their courses), joined date, status, actions
- **Filters:** search by name/email, filter by plan, filter by status
- **Edit:** name, email, plan, validity date, active status
- **Suspend:** adds `suspended_at` (new column) — blocks login + hides their courses from public listing

### 2.3 Student Management
- **Routes:**
  - `GET /app/admin/students` — list
  - `GET /app/admin/students/{user}` — show (with enrollment history)
  - `GET /app/admin/students/{user}/edit` — edit
  - `PUT /app/admin/students/{user}` — update
  - `POST /app/admin/students/{user}/suspend` / `restore`
  - `DELETE /app/admin/students/{user}` — delete
- **List view:** name, email, # enrollments, total spent, joined date, status, actions
- **Filters:** search, status
- **Show:** full enrollment history with amounts, trainers, courses

### 2.4 Course Oversight
- **Routes:**
  - `GET /app/admin/courses` — list ALL courses across all trainers
  - `GET /app/admin/courses/{course}` — show details + materials + enrollment stats
  - `GET /app/admin/courses/{course}/edit` — edit any course
  - `PUT /app/admin/courses/{course}` — update
  - `POST /app/admin/courses/{course}/unpublish` — set status=closed
  - `POST /app/admin/courses/{course}/publish` — set status=open
  - `DELETE /app/admin/courses/{course}` — delete
- **List view:** title, trainer, category, price, status, # enrollments, created date, actions
- **Filters:** search, trainer, category, status, price range
- **Admin can edit all fields** including title/description/price — regardless of trainer ownership

### 2.5 Reporting
- **Route:** `app.admin.reports.index` → `/app/admin/reports`
- **Tabs/sections:**
  - **Revenue:** total revenue, by trainer (top 10), by month (last 12 months), by payment method
  - **Enrollments:** total, by status, by course (top 10), by month
  - **Users:** growth chart (last 12 months), active vs inactive
- **Export:** each section has "Export CSV" and "Export PDF" buttons
  - Routes: `GET /app/admin/reports/{section}/export/{format}` where section ∈ `revenue|enrollments|users` and format ∈ `csv|pdf`
  - PDF uses `barryvdh/laravel-dompdf` (install via `composer require barryvdh/laravel-dompdf`)
  - CSV uses Laravel's built-in `Response::streamDownload` with manual CSV generation — no extra package needed

---

## 3. Database Changes

### 3.1 `users` — add status columns
```php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('suspended_at')->nullable()->after('validity');
    $table->text('suspension_reason')->nullable()->after('suspended_at');
    $table->softDeletes()->after('remember_token');
});
```
- Add `SoftDeletes` trait to `User` model
- Update login/auth: block login when `suspended_at IS NOT NULL`

### 3.2 No schema changes needed for courses / enrollments (existing columns suffice).

---

## 4. Authorization

### 4.1 Layer 1: middleware `admin` (already exists)
Wraps all `/app/admin/*` routes.

### 4.2 Layer 2: policies
- `UserPolicy` — `viewAny`, `view`, `update`, `delete`, `suspend` (admin only; admins cannot suspend/delete other admins)
- `CoursePolicy` — admin method bypass; trainers can only manage own
- Register in `AuthServiceProvider` (or use Laravel 12 auto-discovery via `App\Policies` convention — already in place)

---

## 5. Controllers & Views

### 5.1 New controllers (all under `App\Http\Controllers\Admin\` namespace)
| Controller | Methods |
|---|---|
| `DashboardController` | `index` |
| `TrainerController` | `index`, `show`, `edit`, `update`, `suspend`, `restore`, `destroy` |
| `StudentController` | `index`, `show`, `edit`, `update`, `suspend`, `restore`, `destroy` |
| `CourseController` (admin) | `index`, `show`, `edit`, `update`, `publish`, `unpublish`, `destroy` |
| `ReportController` | `index` (or split into `revenue`, `enrollments`, `users`) |

### 5.2 Views (under `resources/views/admin/`)
- `admin/dashboard.blade.php`
- `admin/trainers/{index,show,edit}.blade.php`
- `admin/students/{index,show,edit}.blade.php`
- `admin/courses/{index,show,edit}.blade.php`
- `admin/reports/index.blade.php`

### 5.3 Layout
- Reuse existing [layouts/app.blade.php](resources/views/layouts/app.blade.php) — no separate admin layout needed
- Update [sidebar.blade.php](resources/views/layouts/partials/sidebar.blade.php): replace admin stub links (`href="#"`) with real routes, add active-highlighting

---

## 6. Build Order (Tasks)

1. **Migrations + User/Course model changes** — soft deletes on both, `suspended_at` + `suspension_reason` on users; update login to block suspended users; hide suspended trainers' courses from public listing.
2. **Admin namespace + routing** — register `App\Http\Controllers\Admin` namespace; scaffold empty controllers; redirect `/app` → `/app/admin` for admins.
3. **Admin dashboard** — stat cards + recent lists + revenue chart.
4. **Trainer management** — list → show → edit → suspend/restore/delete.
5. **Student management** — list → show → edit → suspend/restore/delete.
6. **Course oversight** — list → show → edit → publish/unpublish/delete.
7. **Reporting (UI)** — revenue + enrollments + users sections with charts.
8. **Reporting (Export)** — install `barryvdh/laravel-dompdf`; CSV + PDF endpoints for each report section.
9. **Sidebar + polish** — replace stub links, add breadcrumbs, empty states.
10. **Testing** — Tinker smoke tests + authenticated route checks like Phase 6.

---

## 7. Testing Checklist

- [ ] Non-admin blocked from all `/app/admin/*` routes (403)
- [ ] Admin sees admin dashboard instead of student dashboard on `/app`
- [ ] Suspended trainer's login is blocked
- [ ] Suspended trainer's courses hidden from public `/courses` listing
- [ ] Soft-deleted user's enrollments still resolve (enrollment.user foreign key)
- [ ] Admin can edit any trainer's course
- [ ] Admin cannot suspend or delete another admin
- [ ] Reporting page totals match raw DB queries
- [ ] Empty states render for no users / no courses / no enrollments

---

## 8. Resolved Decisions

1. **Delete policy:** Soft delete users and courses. Both get `SoftDeletes` trait and `deleted_at` column. Enrollment history preserved via `onDelete('set null')` already in place.
2. **Admin dashboard:** Separate `/app/admin` route. On login/landing at `/app`, if user has `admin` role redirect to `/app/admin`.
3. **Suspended users:** Block login entirely. `AuthenticatedSessionController` (or a custom guard) rejects with a clear error message (e.g. "Your account has been suspended. Please contact support.") and logs them out if already logged in.
4. **Suspended trainer's courses:** Hide from public `/courses` listing and `/courses/{slug}` detail. Existing paid enrollees retain full access (learn routes unaffected).
5. **User creation:** Admin panel manages existing users only. No create-user form in Phase 7.
6. **Email notifications:** Skipped for Phase 7. Optionally log suspend/delete actions to Laravel log.
7. **CSV/PDF export:** **Included in Phase 7.** Export endpoints on reports page:
   - CSV: revenue by trainer, enrollments list, user list — via Laravel's built-in `Response::streamDownload` or `League\Csv`
   - PDF: revenue summary + enrollments summary — using `barryvdh/laravel-dompdf` package (needs install)
   - Each report section gets "Export CSV" and "Export PDF" buttons

---

*Created: 2026-04-15. Follows same structure as PHASE-6-STUDENT-LEARNING-PLAN.md.*
