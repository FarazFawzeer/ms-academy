<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'Daily Attendance'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0"><?php echo e(\Carbon\Carbon::parse($date)->format('l, d M Y')); ?></h5>
                    <p class="card-subtitle mb-0">Switch ON every coach who worked on this day. Salary is added automatically.</p>
                </div>
                <a href="<?php echo e(route('admin.coaches.salary')); ?>" class="btn btn-outline-primary">View Salary</a>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-8 col-md-3">
                    <input type="date" name="date" id="dateInput" value="<?php echo e($date); ?>"
                        max="<?php echo e(today()->toDateString()); ?>" class="form-control">
                </div>
                <div class="col-4 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Go</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Coach</th>
                            <th>Rate (LKR)</th>
                            <th class="text-end">Worked today?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $coaches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $present = $worked->has($c->id); $paid = $present && $worked[$c->id] !== null; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo e($c->photo_url); ?>" class="avatar-sm rounded-circle"
                                            style="object-fit:cover" alt="">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($c->name); ?></h6>
                                            <small class="text-muted"><?php echo e($c->phone); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e(number_format($c->daily_rate)); ?></td>
                                <td class="text-end">
                                    <?php if($paid): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input worked-switch" type="checkbox" role="switch"
                                                style="width:3rem;height:1.5rem;cursor:pointer"
                                                data-id="<?php echo e($c->id); ?>" <?php echo e($present ? 'checked' : ''); ?>>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No active coaches. <a
                                        href="<?php echo e(route('admin.coaches.create')); ?>">Add one</a>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var tpl = "<?php echo e(route('admin.coaches.workday.toggle', ['coach' => '__ID__'])); ?>";
            var date = "<?php echo e($date); ?>";

            document.getElementById('dateInput').addEventListener('change', function() {
                this.form.submit();
            });

            document.querySelectorAll('.worked-switch').forEach(function(sw) {
                sw.addEventListener('change', async function() {
                    var want = sw.checked;
                    sw.disabled = true;
                    var d = await AC.call(tpl.replace('__ID__', sw.dataset.id), {
                        body: {
                            date: date,
                            present: want ? 1 : 0
                        }
                    });
                    sw.disabled = false;
                    if (d.success) {
                        AC.toast(d.message);
                    } else {
                        sw.checked = !want; // put the switch back
                        AC.toast(d.message || 'Could not save.', 'error');
                    }
                });
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Coach Attendance'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/coaches/workdays.blade.php ENDPATH**/ ?>