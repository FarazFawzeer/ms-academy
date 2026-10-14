<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'History'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0"><?php echo e(\Carbon\Carbon::parse($date)->format('l, d M Y')); ?></h5>
                    <p class="card-subtitle mb-0"><?php echo e($rows->count()); ?> player(s) attended.</p>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-6 col-md-3">
                    <input type="date" name="date" value="<?php echo e($date); ?>" max="<?php echo e(today()->toDateString()); ?>"
                        class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <select name="batch_id" class="form-select">
                        <option value="">All batches</option>
                        <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($b->id); ?>" <?php echo e($batchId == $b->id ? 'selected' : ''); ?>><?php echo e($b->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player</th>
                            <th class="d-none d-sm-table-cell">Batch</th>
                            <th>Time</th>
                            <th class="d-none d-sm-table-cell">How</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <a href="<?php echo e(route('admin.players.show', $r->player_id)); ?>"
                                        class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                                        <img src="<?php echo e($r->photo_path ? asset($r->photo_path) : asset('images/users/avatar-6.jpg')); ?>"
                                            class="avatar-sm rounded-circle" style="object-fit:cover" alt=""
                                            loading="lazy">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($r->name); ?></h6>
                                            <small class="text-muted"><?php echo e($r->player_no); ?></small>
                                        </div>
                                    </a>
                                </td>
                                <td class="d-none d-sm-table-cell"><?php echo e($r->batch_name ?? '-'); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($r->created_at)->format('h:i A')); ?></td>
                                <td class="d-none d-sm-table-cell"><?php echo e($r->method === 'qr' ? 'QR scan' : 'Manual'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No attendance on this day.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Attendance History'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/attendance/history.blade.php ENDPATH**/ ?>