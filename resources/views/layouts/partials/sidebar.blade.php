<div class="settings-sidebar bg-light p-3 rounded">
    <div>
        <h6 class="mb-3">Main Menu</h6>
        <ul class="mb-3 pb-1">
            <li>
                <a href="{{ route('app.dashboard') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.dashboard') || request()->routeIs('app.admin.dashboard') ? 'active' : '' }}">
                    <i class="isax isax-home-2 me-2"></i><span>Dashboard</span>
                </a>
            </li>
        </ul>

        @if(in_array('student', Auth::user()->roles ?? []))
        <h6 class="mb-3">Student Menu</h6>
        <ul class="mb-3 pb-1">
            <li>
                <a href="{{ route('app.my-courses.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.my-courses.*') ? 'active' : '' }}">
                    <i class="isax isax-book me-2"></i><span>My Courses</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.my-purchases.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.my-purchases.*') ? 'active' : '' }}">
                    <i class="isax isax-receipt-1 me-2"></i><span>Purchase History</span>
                </a>
            </li>
        </ul>
        @endif

        @if(in_array('trainer', Auth::user()->roles ?? []))
        <h6 class="mb-3">Trainer Menu</h6>
        <ul class="mb-3 pb-1">
            <li>
                <a href="{{ route('app.courses.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.courses.*') ? 'active' : '' }}">
                    <i class="isax isax-book me-2"></i><span>My Courses</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.trainer.enrollments.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.trainer.enrollments.*') ? 'active' : '' }}">
                    <i class="isax isax-people me-2"></i><span>Enrollments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.trainer.settings.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.trainer.settings.*') ? 'active' : '' }}">
                    <i class="isax isax-setting-2 me-2"></i><span>Payment Settings</span>
                </a>
            </li>
        </ul>
        @endif

        @if(in_array('admin', Auth::user()->roles ?? []))
        <h6 class="mb-3">Admin Section</h6>
        <ul class="mb-3 pb-1">
            <li>
                <a href="{{ route('app.admin.trainers.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.admin.trainers.*') ? 'active' : '' }}">
                    <i class="isax isax-teacher me-2"></i><span>Manage Trainer</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.admin.courses.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.admin.courses.*') ? 'active' : '' }}">
                    <i class="isax isax-book-square me-2"></i><span>Manage Courses</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.admin.students.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.admin.students.*') ? 'active' : '' }}">
                    <i class="isax isax-people me-2"></i><span>Manage Student</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.admin.reports.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.admin.reports.*') ? 'active' : '' }}">
                    <i class="isax isax-chart-21 me-2"></i><span>Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('app.admin.settings.index') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.admin.settings.*') ? 'active' : '' }}">
                    <i class="isax isax-setting-2 me-2"></i><span>Settings</span>
                </a>
            </li>
        </ul>
        @endif

        <h6 class="mb-3">Account Settings</h6>
        <ul>
            <li>
                <a href="{{ route('app.profile.edit') }}" class="d-inline-flex align-items-center {{ request()->routeIs('app.profile.*') ? 'active' : '' }}">
                    <i class="isax isax-user me-2"></i><span>Profile</span>
                </a>
            </li>
        </ul>

        <!-- Logout Button - Separate from menu -->
        <div class="mt-4 pt-3 sidebar-logout">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 d-inline-flex align-items-center justify-content-center">
                    <i class="isax isax-logout me-2"></i><span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
