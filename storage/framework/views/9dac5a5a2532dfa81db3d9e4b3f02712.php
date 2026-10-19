<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'Salary'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Salary - <?php echo e(\Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y')); ?></h5>
                    <p class="card-subtitle mb-0">Days worked x daily rate. Press "Mark paid" after you hand over the salary.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo e(route('admin.coaches.workdays')); ?>" class="btn btn-outline-primary">Daily Attendance</a>
                    <a href="<?php echo e(route('admin.reports.export', ['type' => 'salary', 'from' => $month . '-01', 'to' => \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString()])); ?>"
                        class="btn btn-outline-secondary">Download Excel</a>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-8 col-md-3">
                    <input type="month" name="month" id="monthInput" value="<?php echo e($month); ?>" class="form-control">
                </div>
                <div class="col-4 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row row-cols-3 g-3 mb-3">
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Days worked</p>
                        <h4 class="mb-0"><?php echo e(number_format($sum['days'])); ?></h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Total (LKR)</p>
                        <h4 class="mb-0"><?php echo e(number_format($sum['total'])); ?></h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Still to pay (LKR)</p>
                        <h4 class="mb-0 <?php echo e($sum['unpaid'] > 0 ? 'text-danger' : 'text-success'); ?>">
                            <?php echo e(number_format($sum['unpaid'])); ?></h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Coach</th>
                            <th>Days</th>
                            <th class="d-none d-md-table-cell">Rate</th>
                            <th>Total</th>
                            <th>To pay</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo e($r->photo_path ? asset($r->photo_path) : asset('images/users/avatar-6.jpg')); ?>"
                                            class="avatar-sm rounded-circle" style="object-fit:cover" alt="">
                                        <h6 class="mb-0"><?php echo e($r->name); ?></h6>
                                    </div>
                                </td>
                                <td><?php echo e($r->days); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo e(number_format($r->daily_rate)); ?></td>
                                <td><?php echo e(number_format($r->total)); ?></td>
                                <td>
                                    <?php if($r->unpaid > 0): ?>
                                        <span class="badge bg-danger"><?php echo e(number_format($r->unpaid)); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo e($r->days ? 'Paid' : '-'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-days"
                                        data-id="<?php echo e($r->id); ?>" data-name="<?php echo e($r->name); ?>"
                                        <?php echo e($r->days ? '' : 'disabled'); ?>>View days</button>
                                    <button type="button" class="btn btn-sm btn-success btn-pay" data-id="<?php echo e($r->id); ?>"
                                        data-name="<?php echo e($r->name); ?>" data-amount="<?php echo e(number_format($r->unpaid)); ?>"
                                        <?php echo e($r->unpaid > 0 ? '' : 'disabled'); ?>>Mark paid</button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No coaches yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var month = "<?php echo e($month); ?>";
            var daysTpl = "<?php echo e(route('admin.coaches.days', ['coach' => '__ID__'])); ?>";
            var payUrl = "<?php echo e(route('admin.coaches.salary.pay')); ?>";

            document.getElementById('monthInput').addEventListener('change', function() {
                this.form.submit();
            });

            document.querySelectorAll('.btn-days').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var rows = await AC.call(daysTpl.replace('__ID__', b.dataset.id) + '?month=' + month, {
                        method: 'GET'
                    });
                    if (!Array.isArray(rows)) {
                        return Swal.fire('Error', rows.message || 'Could not load.', 'error');
                    }
                    var html = '<div class="text-start">' + rows.map(function(r) {
                        return '<div class="d-flex justify-content-between border-bottom py-1"><span>' + AC.esc(r.date) +
                            '</span><span>LKR ' + AC.esc(r.amount) + ' ' +
                            (r.paid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>') +
                            '</span></div>';
                    }).join('') + '</div>';
                    Swal.fire({
                        title: b.dataset.name,
                        html: html,
                        confirmButtonText: 'Close'
                    });
                });
            });

            document.querySelectorAll('.btn-pay').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var ok = await AC.ask('Mark as paid?', b.dataset.name + ': LKR ' + b.dataset.amount +
                        ' for this month will be marked as paid. Paid days can no longer be changed.', 'Yes, mark paid');
                    if (!ok) return;
                    var d = await AC.call(payUrl, {
                        body: {
                            coach_id: b.dataset.id,
                            month: month
                        }
                    });
                    if (d.success) {
                        AC.toast(d.message);
                        setTimeout(function() {
                            location.reload();
                        }, 700);
                    } else {
                        Swal.fire('Error', AC.errors(d).replace(/<br>/g, ' '), 'error');
                    }
                });
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Coach Salary'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/coaches/salary.blade.php ENDPATH**/ ?>