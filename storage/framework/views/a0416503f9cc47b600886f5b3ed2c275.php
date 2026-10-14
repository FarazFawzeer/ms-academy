<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Reports', 'subtitle' => 'Summary'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0"><?php echo e(\Carbon\Carbon::parse($from)->format('d M Y')); ?> -
                        <?php echo e(\Carbon\Carbon::parse($to)->format('d M Y')); ?></h5>
                    <p class="card-subtitle mb-0">Pick any period. Excel files use the same dates.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php $__currentLoopData = ['attendance' => 'Attendance', 'payments' => 'Payments', 'salary' => 'Coach Salary']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.reports.export', ['type' => $key, 'from' => $from, 'to' => $to])); ?>"
                            class="btn btn-outline-secondary btn-sm">
                            <iconify-icon icon="solar:download-minimalistic-outline" class="me-1"></iconify-icon>
                            <?php echo e($label); ?> Excel
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-6 col-md-3">
                    <input type="date" name="from" value="<?php echo e($from); ?>" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="to" value="<?php echo e($to); ?>" class="form-control">
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <a href="<?php echo e(route('admin.reports.index')); ?>" class="btn btn-light">This month</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row row-cols-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Sessions attended</p>
                        <h4 class="mb-0"><?php echo e(number_format($sum->sessions)); ?></h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Fees received (LKR)</p>
                        <h4 class="mb-0 text-success"><?php echo e(number_format($sum->revenue)); ?></h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Coach salary (LKR)</p>
                        <h4 class="mb-0 text-danger"><?php echo e(number_format($sum->salary)); ?></h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">New players</p>
                        <h4 class="mb-0"><?php echo e(number_format($sum->new_players)); ?></h4>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <span class="text-muted">Fees minus coach salary:</span>
                <?php $net = $sum->revenue - $sum->salary; ?>
                <strong class="<?php echo e($net >= 0 ? 'text-success' : 'text-danger'); ?>">LKR <?php echo e(number_format($net)); ?></strong>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">By Batch</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Sessions</th>
                                    <th>Players</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $byBatch; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($b->batch ?? 'No batch'); ?></td>
                                        <td><?php echo e(number_format($b->sessions)); ?></td>
                                        <td><?php echo e(number_format($b->players)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No attendance in this period.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">By Player</h5>
                    <p class="card-subtitle mb-0">Active players, most sessions first. Players with 0 may be absent for a long time.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Batch</th>
                                    <th>Sessions</th>
                                    <th class="d-none d-md-table-cell">Last seen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $byPlayer; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><a href="<?php echo e(route('admin.players.show', $p->id)); ?>"><?php echo e($p->name); ?></a><br>
                                            <small class="text-muted"><?php echo e($p->player_no); ?></small></td>
                                        <td class="d-none d-sm-table-cell"><?php echo e($p->batch ?? '-'); ?></td>
                                        <td><?php echo e($p->sessions); ?></td>
                                        <td class="d-none d-md-table-cell">
                                            <?php echo e($p->last_seen ? \Carbon\Carbon::parse($p->last_seen)->format('d M') : '-'); ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Reports'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/reports/index.blade.php ENDPATH**/ ?>