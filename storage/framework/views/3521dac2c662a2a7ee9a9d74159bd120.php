<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'All Players'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        /* Filter bar: right aligned, one clean row on desktop, stacked on mobile */
        .pl-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
        }

        .pl-filters .form-control,
        .pl-filters .form-select {
            height: 38px;
            font-size: 14px;
        }

        .pl-filters .pl-search {
            position: relative;
            flex: 0 1 280px;
            min-width: 200px;
        }

        .pl-filters .pl-search iconify-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #8a94a6;
            pointer-events: none;
        }

        .pl-filters .pl-search .form-control {
            padding-left: 34px;
        }

        .pl-filters .pl-select {
            flex: 0 0 160px;
        }

        .pl-clear {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 500;
            color: #dc3545;
            text-decoration: none;
            white-space: nowrap;
        }

        .pl-clear:hover {
            text-decoration: underline;
            color: #b02a37;
        }

        @media (max-width: 767.98px) {
            .pl-filters {
                justify-content: stretch;
            }

            .pl-filters .pl-search {
                flex: 1 1 100%;
            }

            .pl-filters .pl-select {
                flex: 1 1 calc(50% - .25rem);
            }
        }

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

        .act-pay {
            color: #16a34a;
        }

        .act-view {
            color: #2563eb;
        }

        .act-edit {
            color: #f59e0b;
        }

        .act-print {
            color: #7c3aed;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Player List</h5>
                    <p class="card-subtitle mb-0"><?php echo e($players->total()); ?> player(s) found.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo e(route('admin.players.cards', array_filter(['batch_id' => request('batch_id')]))); ?>"
                        target="_blank" class="btn btn-outline-secondary">
                        <iconify-icon icon="solar:printer-outline" class="me-1"></iconify-icon> Print ID Cards
                    </a>
                    <a href="<?php echo e(route('admin.players.create')); ?>" class="btn btn-primary">
                        <iconify-icon icon="solar:user-plus-outline" class="me-1"></iconify-icon> Add Player
                    </a>
                </div>
            </div>

            
            <form method="GET" id="playerFilter" class="pl-filters">
                <div class="pl-search">
                    <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                    <input type="search" name="q" id="plSearch" value="<?php echo e(request('q')); ?>" class="form-control"
                        placeholder="Search name, ID or phone" autocomplete="off">
                </div>

                <select name="batch_id" class="form-select pl-select" onchange="this.form.submit()">
                    <option value="">All batches</option>
                    <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b->id); ?>" <?php echo e(request('batch_id') == $b->id ? 'selected' : ''); ?>>
                            <?php echo e($b->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>

                <select name="status" class="form-select pl-select" onchange="this.form.submit()">
                    <option value="active" <?php echo e($status === 'active' ? 'selected' : ''); ?>>Active</option>
                    <option value="inactive" <?php echo e($status === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                    <option value="all" <?php echo e($status === 'all' ? 'selected' : ''); ?>>All</option>
                </select>

                <?php if(request('q') || request('batch_id') || (request('status') && request('status') !== 'active')): ?>
                    <a href="<?php echo e(route('admin.players.index')); ?>" class="pl-clear">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player</th>
                            <th class="d-none d-md-table-cell">Batch</th>
                            <th class="d-none d-lg-table-cell">Guardian</th>
                            <th>Sessions</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $players; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <a href="<?php echo e(route('admin.players.show', $p)); ?>"
                                        class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                                        <img src="<?php echo e($p->photo_url); ?>" alt="<?php echo e($p->name); ?>" class="avatar-sm rounded-circle"
                                            style="object-fit:cover" loading="lazy">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($p->name); ?>

                                                <?php if (! ($p->is_active)): ?>
                                                    <span class="badge ms-1" style="background:#6b72801a;color:#4b5563">Inactive</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted"><?php echo e($p->player_no); ?> · <?php echo e($p->age); ?> yrs</small>
                                        </div>
                                    </a>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo e($p->batch?->name ?? '-'); ?></td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo e($p->guardian_name); ?><br>
                                    <small class="text-muted"><?php echo e($p->guardian_phone); ?></small>
                                </td>
                                <td data-balance="<?php echo e($p->id); ?>"><?php echo e($p->balanceBadge()); ?></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="act-ico act-pay" title="Receive payment"
                                        onclick="openPayment(<?php echo e($p->id); ?>, <?php echo \Illuminate\Support\Js::from($p->name)->toHtml() ?>)">
                                        <iconify-icon icon="solar:wallet-bold-duotone"></iconify-icon>
                                    </button>
                                    <a href="<?php echo e(route('admin.players.show', $p)); ?>" class="act-ico act-view"
                                        title="View profile">
                                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                                    </a>
                                    <a href="<?php echo e(route('admin.players.edit', $p)); ?>" class="act-ico act-edit"
                                        title="Edit player">
                                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                    </a>
                                    <a href="<?php echo e(route('admin.players.cards', ['ids' => $p->id])); ?>" target="_blank"
                                        class="act-ico act-print" title="Print ID card">
                                        <iconify-icon icon="solar:printer-bold-duotone"></iconify-icon>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No players found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?php echo e($players->withQueryString()->links()); ?>

            </div>
        </div>
    </div>

    <?php echo $__env->make('players._payment-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        (function() {
            var form = document.getElementById('playerFilter');
            var input = document.getElementById('plSearch');
            var timer;

            // Auto search 0.5s after the user stops typing
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    form.submit();
                }, 500);
            });

            // Enter key submits immediately
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timer);
                }
            });

            // After the page reloads, keep the cursor in the search box
            if (input.value) {
                input.focus();
                var len = input.value.length;
                input.setSelectionRange(len, len);
            }
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Players'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/players/index.blade.php ENDPATH**/ ?>