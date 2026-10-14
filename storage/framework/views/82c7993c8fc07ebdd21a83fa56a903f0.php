<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Batches', 'subtitle' => 'Age Groups'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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

        .act-players {
            color: #2563eb;
        }

        .act-edit {
            color: #f59e0b;
        }

        .act-del {
            color: #dc2626;
        }

        .st-badge {
            display: inline-block;
            width: 80px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 6px 0;
            border-radius: 6px;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="card-title mb-0">Batches</h5>
                <p class="card-subtitle mb-0">Group players by age or training time, e.g. U9, U12, Girls, Weekend.</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnNew">
                <iconify-icon icon="solar:add-circle-outline" class="me-1"></iconify-icon> New Batch
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Batch</th>
                            <th class="d-none d-md-table-cell">Schedule</th>
                            <th class="d-none d-sm-table-cell">Coach</th>
                            <th>Players</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr id="batch-<?php echo e($b->id); ?>">
                                <td><strong><?php echo e($b->name); ?></strong></td>
                                <td class="d-none d-md-table-cell"><?php echo e($b->schedule ?? '-'); ?></td>
                                <td class="d-none d-sm-table-cell"><?php echo e($b->coach?->name ?? '-'); ?></td>
                                <td><?php echo e($b->players_count); ?></td>
                                <td>
                                    <span class="st-badge"
                                        style="background:<?php echo e($b->is_active ? '#15803d' : '#4b5563'); ?>1a;color:<?php echo e($b->is_active ? '#15803d' : '#4b5563'); ?>"><?php echo e($b->is_active ? 'Active' : 'Inactive'); ?></span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?php echo e(route('admin.players.index', ['batch_id' => $b->id])); ?>"
                                        class="act-ico act-players" title="Players in this batch">
                                        <iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon>
                                    </a>
                                    <button type="button" class="act-ico act-edit btn-edit"
                                        data-id="<?php echo e($b->id); ?>" data-name="<?php echo e($b->name); ?>"
                                        data-schedule="<?php echo e($b->schedule); ?>" data-coach="<?php echo e($b->coach_id); ?>"
                                        data-active="<?php echo e($b->is_active ? 1 : 0); ?>" title="Edit batch">
                                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                    </button>
                                    <button type="button" class="act-ico act-del btn-del"
                                        data-id="<?php echo e($b->id); ?>" title="Delete batch">
                                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No batches yet. Create the first one.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="batchForm" method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="batchTitle">New Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="batchMessage"></div>

                    <div class="mb-3">
                        <label class="form-label">Batch Name</label>
                        <input type="text" name="name" id="bName" class="form-control" placeholder="Ex: U12" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Training Schedule <span class="text-muted">(optional)</span></label>
                        <input type="text" name="schedule" id="bSchedule" class="form-control"
                            placeholder="Ex: Mon & Wed, 4.00 - 6.00 PM">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coach <span class="text-muted">(optional)</span></label>
                        <select name="coach_id" id="bCoach" class="form-select">
                            <option value="">No coach assigned</option>
                            <?php $__currentLoopData = $coaches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($c->id); ?>"><?php echo e($c->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="bActive"
                            value="1" checked>
                        <label class="form-check-label" for="bActive">Batch is active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Batch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var storeUrl = "<?php echo e(route('admin.batches.store')); ?>";
            var updateTpl = "<?php echo e(route('admin.batches.update', ['batch' => '__ID__'])); ?>";
            var deleteTpl = "<?php echo e(route('admin.batches.destroy', ['batch' => '__ID__'])); ?>";
            var modalEl = document.getElementById('batchModal');
            var form = document.getElementById('batchForm');
            var msg = document.getElementById('batchMessage');

            function open(title, action, v) {
                document.getElementById('batchTitle').textContent = title;
                form.setAttribute('action', action);
                msg.innerHTML = '';
                document.getElementById('bName').value = v.name || '';
                document.getElementById('bSchedule').value = v.schedule || '';
                document.getElementById('bCoach').value = v.coach || '';
                document.getElementById('bActive').checked = v.active !== '0';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            document.getElementById('btnNew').addEventListener('click', function() {
                open('New Batch', storeUrl, {});
            });

            document.querySelectorAll('.btn-edit').forEach(function(b) {
                b.addEventListener('click', function() {
                    open('Edit Batch', updateTpl.replace('__ID__', b.dataset.id), {
                        name: b.dataset.name,
                        schedule: b.dataset.schedule,
                        coach: b.dataset.coach,
                        active: b.dataset.active
                    });
                });
            });

            document.querySelectorAll('.btn-del').forEach(function(b) {
                b.addEventListener('click', function() {
                    AC.act(deleteTpl.replace('__ID__', b.dataset.id), {
                        method: 'DELETE',
                        confirm: {
                            title: 'Delete this batch?',
                            text: 'Only empty batches can be deleted.',
                            button: 'Yes, delete'
                        },
                        onSuccess: function() {
                            document.getElementById('batch-' + b.dataset.id).remove();
                        }
                    });
                });
            });

            AC.form(form, {
                box: msg,
                onSuccess: function(d) {
                    AC.toast(d.message);
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    setTimeout(function() {
                        location.reload();
                    }, 600);
                }
            });
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Batches'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/batches/index.blade.php ENDPATH**/ ?>