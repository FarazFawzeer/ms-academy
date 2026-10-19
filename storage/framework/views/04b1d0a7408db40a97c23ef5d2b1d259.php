<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'Age Groups'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        .ag-card {
            border: 1px solid rgba(100, 116, 139, .2);
            border-radius: 12px;
            padding: 16px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: border-color .12s ease, box-shadow .12s ease;
        }

        .ag-card:hover {
            border-color: var(--ag);
            box-shadow: 0 4px 14px rgba(15, 23, 42, .06);
        }

        .ag-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .ag-name {
            display: inline-block;
            font-size: 20px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 8px;
            color: var(--ag);
            background: color-mix(in srgb, var(--ag) 10%, transparent);
        }

        .ag-range {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }

        .ag-total {
            text-align: right;
            line-height: 1.1;
        }

        .ag-total b {
            display: block;
            font-size: 28px;
            color: #334155;
        }

        .ag-total small {
            color: #94a3b8;
        }

        .ag-split {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }

        .ag-split div {
            flex: 1;
            text-align: center;
            background: rgba(100, 116, 139, .07);
            border-radius: 8px;
            padding: 6px 4px;
            font-size: 11px;
            color: #64748b;
        }

        .ag-split b {
            display: block;
            font-size: 16px;
            color: #334155;
        }

        .ag-links {
            margin-top: auto;
            display: flex;
            gap: 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .ag-links a {
            color: var(--ag);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .ag-links a:hover {
            text-decoration: underline;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Age Groups</h5>
            <p class="card-subtitle mb-0">Automatic from each player's date of birth. Players move up on their birthday,
                nothing to update by hand.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-sm-6 col-xl-4">
                        <div class="ag-card" style="--ag: <?php echo e(\App\Support\AgeGroup::colour($g['label'])); ?>">
                            <div class="ag-top">
                                <div>
                                    <span class="ag-name"><?php echo e($g['label']); ?></span>
                                    <div class="ag-range"><?php echo e($g['range']); ?></div>
                                </div>
                                <div class="ag-total"><b><?php echo e($g['total']); ?></b><small>active players</small></div>
                            </div>

                            <div class="ag-split">
                                <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div><b><?php echo e($g['only'][$key] ?? 0); ?></b><?php echo e($s['label']); ?> only</div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <div><b><?php echo e($g['both']); ?></b>Both days</div>
                            </div>

                            <div class="ag-links">
                                <a href="<?php echo e(route('admin.players.index', ['group' => $g['label']])); ?>">
                                    <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon> Players</a>
                                <a href="<?php echo e(route('admin.attendance.register', ['group' => $g['label']])); ?>">
                                    <iconify-icon icon="solar:calendar-outline"></iconify-icon> Register</a>
                                <a href="<?php echo e(route('admin.players.cards', ['group' => $g['label']])); ?>" target="_blank">
                                    <iconify-icon icon="solar:printer-outline"></iconify-icon> ID Cards</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Age Groups'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/groups/index.blade.php ENDPATH**/ ?>