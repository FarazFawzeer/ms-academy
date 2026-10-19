@php
    $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']);

    // one cheap indexed COUNT per page load - drives the red badge on "Reminders"
    $pendingReminders = \App\Models\Player::where('is_active', true)->where('reminder_pending', true)->count();

    $open = fn(...$patterns) => request()->routeIs(...$patterns);
@endphp

<div class="app-sidebar">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ route('dashboard') }}" class="logo-dark">
            <img src="/images/ms-logo.png" class="logo-sm" alt="logo sm">
            <img src="/images/ms-logo.png" class="logo-lg" alt="logo dark" style="width: 75px; height: 75px;">
        </a>

        <a href="{{ route('dashboard') }}" class="logo-light">
            <img src="/images/ms-logo.png" class="logo-sm" alt="logo sm">
            <img src="/images/ms-logo.png" class="logo-lg" alt="logo light" style="width: 75px; height: 75px;">
        </a>
    </div>

    <div class="scrollbar" data-simplebar>

        <ul class="navbar-nav" id="navbar-nav">

            <li class="menu-title">Menu...</li>

            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ $open('dashboard', 'root') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:home-2-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            {{-- Attendance --}}
            <li class="nav-item">
                <a class="nav-link menu-arrow {{ $open('admin.attendance.*') ? '' : 'collapsed' }}" href="#sidebarAttendance"
                    data-bs-toggle="collapse" role="button"
                    aria-expanded="{{ $open('admin.attendance.*') ? 'true' : 'false' }}" aria-controls="sidebarAttendance">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:qr-code-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Attendance</span>
                </a>
                <div class="collapse {{ $open('admin.attendance.*') ? 'show' : '' }}" id="sidebarAttendance">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.attendance.scan') }}">Scan / Mark</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.attendance.register') }}">Register</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Players --}}
            <li class="nav-item">
                <a class="nav-link menu-arrow {{ $open('admin.players.*', 'admin.groups.*') ? '' : 'collapsed' }}" href="#sidebarPlayers"
                    data-bs-toggle="collapse" role="button"
                    aria-expanded="{{ $open('admin.players.*', 'admin.groups.*') ? 'true' : 'false' }}" aria-controls="sidebarPlayers">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Players</span>
                </a>
                <div class="collapse {{ $open('admin.players.*', 'admin.groups.*') ? 'show' : '' }}" id="sidebarPlayers">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.players.index') }}">All Players</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.players.create') }}">Add Player</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.groups.index') }}">Age Groups</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('admin.players.print') }}">Print ID Cards</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Reminders --}}
            <li class="nav-item">
                <a class="nav-link {{ $open('admin.reminders.*') ? 'active' : '' }}" href="{{ route('admin.reminders.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:bell-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Reminders </span>
                    @if ($pendingReminders > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingReminders }}</span>
                    @endif
                </a>
            </li>

            {{-- Payments --}}
            <li class="nav-item">
                <a class="nav-link {{ $open('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:wallet-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Payments </span>
                </a>
            </li>

            @if ($isAdmin)
                <li class="menu-title">Management...</li>

                {{-- Practice schedule --}}
                <li class="nav-item">
                    <a class="nav-link {{ $open('admin.schedule.*') ? 'active' : '' }}" href="{{ route('admin.schedule.index') }}">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:calendar-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Practice Schedule </span>
                    </a>
                </li>

                {{-- Coaches --}}
                <li class="nav-item">
                    <a class="nav-link menu-arrow {{ $open('admin.coaches.*') ? '' : 'collapsed' }}" href="#sidebarCoaches"
                        data-bs-toggle="collapse" role="button"
                        aria-expanded="{{ $open('admin.coaches.*') ? 'true' : 'false' }}" aria-controls="sidebarCoaches">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:user-id-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Coaches</span>
                    </a>
                    <div class="collapse {{ $open('admin.coaches.*') ? 'show' : '' }}" id="sidebarCoaches">
                        <ul class="nav sub-navbar-nav">
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('admin.coaches.index') }}">All Coaches</a>
                            </li>
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('admin.coaches.create') }}">Add Coach</a>
                            </li>
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('admin.coaches.workdays') }}">Daily Attendance</a>
                            </li>
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('admin.coaches.salary') }}">Salary</a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Reports --}}
                <li class="nav-item">
                    <a class="nav-link {{ $open('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:chart-2-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Reports </span>
                    </a>
                </li>

                {{-- Users (list + Add User button on the page) --}}
                <li class="nav-item">
                    <a class="nav-link {{ $open('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:user-circle-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Users </span>
                    </a>
                </li>
            @endif

            {{-- Profile --}}
            <li class="nav-item">
                <a class="nav-link {{ $open('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:widget-2-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Profile </span>
                </a>
            </li>
        </ul>
    </div>
</div>