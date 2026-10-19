<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'All Coaches'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        /* Action icons: no background, colour only */
        .act-ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            font-size: 22px;
            line-height: 1;
            background: transparent;
            border: 0;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
            transition: transform .12s ease, opacity .12s ease;
        }

        .act-ico:hover {
            transform: scale(1.2);
            opacity: .85;
        }

        .act-edit {
            color: #f59e0b;
        }

        .act-off {
            color: #dc2626;
        }

        .act-on {
            color: #16a34a;
        }

        .st-badge {
            display: inline-block;
            width: 80px;
            text-align: center;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            padding: 6px 0;
            border-radius: 6px;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="card-title mb-0">Coach List</h5>
                <p class="card-subtitle mb-0">Coaches are paid a fixed amount for each day they work.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(route('admin.coaches.workdays')); ?>" class="btn btn-outline-primary">Daily Attendance</a>
                <a href="<?php echo e(route('admin.coaches.create')); ?>" class="btn btn-primary">
                    <iconify-icon icon="solar:user-plus-outline" class="me-1"></iconify-icon> Add Coach
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Coach</th>
                            <th class="d-none d-md-table-cell">Phone</th>
                            <th>Daily Rate (LKR)</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $coaches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo e($c->photo_url); ?>" class="avatar-sm rounded-circle"
                                            style="object-fit:cover" alt="">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($c->name); ?></h6>
                                            <?php if($c->nic): ?>
                                                <small class="text-muted">NIC <?php echo e($c->nic); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo e($c->phone ?? '-'); ?></td>
                                <td><?php echo e(number_format($c->daily_rate)); ?></td>
                                <td>
                                    <span class="st-badge"
                                        style="background:<?php echo e($c->is_active ? '#16a34a' : '#6b7280'); ?>"><?php echo e($c->is_active ? 'Active' : 'Inactive'); ?></span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?php echo e(route('admin.coaches.edit', $c)); ?>" class="act-ico act-edit"
                                        title="Edit coach">
                                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                    </a>
                                    <button type="button"
                                        class="act-ico btn-toggle <?php echo e($c->is_active ? 'act-off' : 'act-on'); ?>"
                                        data-id="<?php echo e($c->id); ?>" data-active="<?php echo e($c->is_active ? 1 : 0); ?>"
                                        title="<?php echo e($c->is_active ? 'Deactivate coach' : 'Activate coach'); ?>">
                                        <iconify-icon
                                            icon="<?php echo e($c->is_active ? 'solar:user-block-bold-duotone' : 'solar:user-check-bold-duotone'); ?>"></iconify-icon>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No coaches yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        var toggleTpl = "<?php echo e(route('admin.coaches.toggle', ['coach' => '__ID__'])); ?>";
        document.querySelectorAll('.btn-toggle').forEach(function(b) {
            b.addEventListener('click', function() {
                AC.act(toggleTpl.replace('__ID__', b.dataset.id), {
                    confirm: b.dataset.active === '1' ? {
                        title: 'Deactivate this coach?',
                        text: 'They will no longer appear in daily attendance. Salary history is kept.',
                        button: 'Yes, deactivate'
                    } : null,
                    onSuccess: function() {
                        setTimeout(function() {
                            location.reload();
                        }, 600);
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Coaches'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/coaches/index.blade.php ENDPATH**/ ?>