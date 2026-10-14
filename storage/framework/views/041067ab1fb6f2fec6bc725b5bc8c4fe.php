<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Payments', 'subtitle' => 'All Payments'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']);
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $methodColors = ['cash' => '#15803d', 'card' => '#1d4ed8', 'bank' => '#6d28d9', 'online' => '#0e7490'];
    ?>

    <style>
        /* Filters: right aligned, auto apply */
        .pay-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
        }

        .pay-filters .form-control {
            height: 38px;
            font-size: 14px;
            width: 160px;
        }

        .pay-filters .sep {
            color: #8a94a6;
            font-size: 13px;
        }

        .pay-chip {
            display: inline-flex;
            align-items: center;
            height: 38px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d5dae3;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            background: transparent;
            white-space: nowrap;
        }

        .pay-chip:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .pay-chip.active {
            background: #2563eb1a;
            border-color: #2563eb;
            color: #1d4ed8;
        }

        @media (max-width: 767.98px) {
            .pay-filters {
                justify-content: stretch;
            }

            .pay-filters .form-control {
                flex: 1 1 calc(50% - .25rem);
                width: auto;
                min-width: 0;
            }

            .pay-filters .sep {
                display: none;
            }
        }

        /* Summary tiles */
        .pay-stat {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border: 1px solid #e5e9f0;
            border-radius: 10px;
            height: 100%;
        }

        .pay-stat .ico {
            flex: 0 0 46px;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .pay-stat .lbl {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        .pay-stat .val {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        /* Badges */
        .pay-badge {
            display: inline-block;
            min-width: 64px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }

        /* Action icons: no background */
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

        .act-del {
            color: #dc2626;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Payments Received</h5>
                    <p class="card-subtitle mb-0">To receive a new payment, open the player and press Pay.</p>
                </div>
                <?php if($isAdmin): ?>
                    <a href="<?php echo e(route('admin.reports.export', ['type' => 'payments', 'from' => $from, 'to' => $to])); ?>"
                        class="btn btn-outline-secondary">
                        <iconify-icon icon="solar:download-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                        Download Excel
                    </a>
                <?php endif; ?>
            </div>

            
            <form method="GET" id="payFilter" class="pay-filters">
                <a href="<?php echo e(request()->url()); ?>?from=<?php echo e($today); ?>&to=<?php echo e($today); ?>"
                    class="pay-chip <?php echo e($from === $today && $to === $today ? 'active' : ''); ?>">Today</a>
                <a href="<?php echo e(request()->url()); ?>?from=<?php echo e($monthStart); ?>&to=<?php echo e($today); ?>"
                    class="pay-chip <?php echo e($from === $monthStart && $to === $today ? 'active' : ''); ?>">This Month</a>

                <input type="date" name="from" value="<?php echo e($from); ?>" class="form-control"
                    onchange="this.form.submit()" aria-label="From date">
                <span class="sep">to</span>
                <input type="date" name="to" value="<?php echo e($to); ?>" class="form-control"
                    onchange="this.form.submit()" aria-label="To date">
            </form>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="pay-stat">
                        <div class="ico" style="background:#2563eb1a;color:#2563eb">
                            <iconify-icon icon="solar:bill-list-bold-duotone"></iconify-icon>
                        </div>
                        <div>
                            <p class="lbl">Payments</p>
                            <p class="val"><?php echo e(number_format($totals->c)); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="pay-stat">
                        <div class="ico" style="background:#16a34a1a;color:#16a34a">
                            <iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon>
                        </div>
                        <div>
                            <p class="lbl">Total (LKR)</p>
                            <p class="val"><?php echo e(number_format($totals->s)); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Player</th>
                            <th>Amount (LKR)</th>
                            <th class="d-none d-sm-table-cell">Sessions</th>
                            <th class="d-none d-md-table-cell">Method</th>
                            <?php if($isAdmin): ?>
                                <th class="text-end">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr id="pay-<?php echo e($p->id); ?>">
                                <td><?php echo e($p->paid_at->format('d M Y')); ?><br><small
                                        class="text-muted"><?php echo e($p->paid_at->format('h:i A')); ?></small></td>
                                <td>
                                    <a href="<?php echo e(route('admin.players.show', $p->player_id)); ?>"
                                        class="fw-semibold"><?php echo e($p->player?->name); ?></a><br>
                                    <small class="text-muted"><?php echo e($p->player?->player_no); ?></small>
                                </td>
                                <td class="fw-semibold"><?php echo e(number_format($p->amount)); ?></td>
                                <td class="d-none d-sm-table-cell">
                                    <span class="pay-badge" style="background:#15803d1a;color:#15803d">+<?php echo e($p->sessions); ?></span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="pay-badge"
                                        style="background:<?php echo e($methodColors[strtolower($p->method)] ?? '#4b5563'); ?>1a;color:<?php echo e($methodColors[strtolower($p->method)] ?? '#4b5563'); ?>"><?php echo e(ucfirst($p->method)); ?></span>
                                </td>
                                <?php if($isAdmin): ?>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="act-ico act-del btn-del"
                                            data-id="<?php echo e($p->id); ?>" title="Remove (entered by mistake)">
                                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="<?php echo e($isAdmin ? 6 : 5); ?>" class="text-center text-muted py-4">No payments in this
                                    period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?php echo e($payments->withQueryString()->links()); ?>

            </div>
        </div>
    </div>

    <?php if($isAdmin): ?>
        <script>
            var delTpl = "<?php echo e(route('admin.payments.destroy', ['payment' => '__ID__'])); ?>";
            document.querySelectorAll('.btn-del').forEach(function(b) {
                b.addEventListener('click', function() {
                    AC.act(delTpl.replace('__ID__', b.dataset.id), {
                        method: 'DELETE',
                        confirm: {
                            title: 'Remove this payment?',
                            text: 'The sessions it added will be taken back from the player.',
                            button: 'Yes, remove'
                        },
                        onSuccess: function() {
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        }
                    });
                });
            });
        </script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Payments'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/payments/index.blade.php ENDPATH**/ ?>