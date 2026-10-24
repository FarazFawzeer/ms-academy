<?php
    $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']);

    // one cheap indexed COUNT per page load - drives the red badge on "Reminders"
    $pendingReminders = \App\Models\Player::where('is_active', true)->where('reminder_pending', true)->count();

    $open = fn(...$patterns) => request()->routeIs(...$patterns);
?>

<div class="app-sidebar">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="<?php echo e(route('dashboard')); ?>" class="logo-dark">
            <img src="/images/ms-logo.png" class="logo-sm" alt="logo sm">
            <img src="/images/ms-logo.png" class="logo-lg" alt="logo dark" style="width: 75px; height: 75px;">
        </a>

        <a href="<?php echo e(route('dashboard')); ?>" class="logo-light">
            <img src="/images/ms-logo.png" class="logo-sm" alt="logo sm">
            <img src="/images/ms-logo.png" class="logo-lg" alt="logo light" style="width: 75px; height: 75px;">
        </a>
    </div>

    <div class="scrollbar" data-simplebar>

        <ul class="navbar-nav" id="navbar-nav">

            <li class="menu-title">Menu...</li>

            
            <li class="nav-item">
                <a class="nav-link <?php echo e($open('dashboard', 'root') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:home-2-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            
            <li class="nav-item">
                <a class="nav-link menu-arrow <?php echo e($open('admin.attendance.*') ? '' : 'collapsed'); ?>" href="#sidebarAttendance"
                    data-bs-toggle="collapse" role="button"
                    aria-expanded="<?php echo e($open('admin.attendance.*') ? 'true' : 'false'); ?>" aria-controls="sidebarAttendance">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:qr-code-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Attendance</span>
                </a>
                <div class="collapse <?php echo e($open('admin.attendance.*') ? 'show' : ''); ?>" id="sidebarAttendance">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="<?php echo e(route('admin.attendance.scan')); ?>">Scan / Mark</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="<?php echo e(route('admin.attendance.register')); ?>">Register</a>
                        </li>
                    </ul>
                </div>
            </li>

            
            <li class="nav-item">
                <a class="nav-link <?php echo e($open('admin.players.*', 'admin.groups.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.players.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Players </span>
                </a>
            </li>

            
            <li class="nav-item">
                <a class="nav-link <?php echo e($open('admin.reminders.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.reminders.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:bell-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Reminders </span>
                    <?php if($pendingReminders > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo e($pendingReminders); ?></span>
                    <?php endif; ?>
                </a>
            </li>

            
            <li class="nav-item">
                <a class="nav-link <?php echo e($open('admin.payments.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.payments.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:wallet-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Payments </span>
                </a>
            </li>

            <?php if($isAdmin): ?>
                <li class="menu-title">Management...</li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e($open('admin.schedule.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.schedule.index')); ?>">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:calendar-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Practice Schedule </span>
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e($open('admin.coaches.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.coaches.index')); ?>">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:user-id-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Coaches </span>
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e($open('admin.reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.reports.index')); ?>">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:chart-2-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Reports </span>
                    </a>
                </li>

                
                <li class="nav-item">
                    <a class="nav-link <?php echo e($open('admin.users.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.users.index')); ?>">
                        <span class="nav-icon">
                            <iconify-icon icon="solar:user-circle-outline"></iconify-icon>
                        </span>
                        <span class="nav-text"> Users </span>
                    </a>
                </li>
            <?php endif; ?>

            
            <li class="nav-item">
                <a class="nav-link <?php echo e($open('admin.profile.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.profile.edit')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:widget-2-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Profile </span>
                </a>
            </li>
        </ul>
    </div>
</div><?php /**PATH F:\Nexas\ms-academy\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>