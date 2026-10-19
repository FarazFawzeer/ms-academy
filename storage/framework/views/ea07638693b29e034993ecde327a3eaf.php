

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => config('academy.name'), 'subtitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']);
        $ph      = asset('images/users/avatar-6.jpg');

        // small helper: "+12%" / "-5%" against a previous value
        $delta = function ($now, $before) {
            if ($before == 0) {
                return null;
            }
            return round((($now - $before) / $before) * 100, 1);
        };
        $revDelta = $delta($stats->month_revenue, $stats->last_month_revenue);
        $dayDiff  = $stats->today_count - $stats->yesterday_count;
    ?>

    <style>
        /* ===== Dashboard palette: teal / sky / indigo on slate ===== */
        .ds {
            --ds-teal: #0f766e;
            --ds-teal-dark: #115e59;
            --ds-teal-soft: rgba(15, 118, 110, .10);
            --ds-slate: #475569;
            --ds-line: rgba(100, 116, 139, .25);
        }

        /* ---- quick actions: 3 tiles on phones, normal buttons on bigger screens ---- */
        .ds-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .ds-actions .btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 10px 6px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
  
        }

        .ds-actions .btn iconify-icon {
            font-size: 22px;
        }

        @media (min-width: 576px) {
            .ds-actions {
                display: flex;
                flex-wrap: wrap;
            }

            .ds-actions .btn {
                flex-direction: row;
                gap: 8px;
                padding: 9px 20px;
                font-size: 14px;
            }

            .ds-actions .btn iconify-icon {
                font-size: 18px;
            }
        }

        .ds .btn-teal {
            background: var(--ds-teal);
            border: 1px solid var(--ds-teal);
            color: #fff;
        }

        .ds .btn-teal:hover {
            background: var(--ds-teal-dark);
            border-color: var(--ds-teal-dark);
            color: #fff;
        }

        .ds .btn-teal-outline {
            background: transparent;
            border: 1px solid var(--ds-teal);
            color: var(--ds-teal);
        }

        .ds .btn-teal-outline:hover {
            background: var(--ds-teal-soft);
            color: var(--ds-teal-dark);
        }

        .ds .btn-slate-outline {
            background: transparent;
            border: 1px solid var(--ds-line);
            color: var(--ds-slate);
        }

        .ds .btn-slate-outline:hover {
            background: rgba(100, 116, 139, .1);
            color: #1e293b;
        }

        /* ---- stat cards ---- */
        .ds-stat {
            display: block;
            height: 100%;
            margin-bottom: 0;
            color: inherit;
            text-decoration: none;
        }

        a.ds-stat:hover {
            border-color: var(--ds-teal);
        }

        .ds-stat .card-body {
            padding: 14px 14px 10px;
        }

        .ds-stat .lbl {
            margin: 0 0 6px;
            font-size: 12.5px;
            line-height: 1.25;
            color: var(--bs-secondary-color, #6c757d);
            min-height: 31px;
            display: flex;
            align-items: flex-start;
        }

        .ds-stat .val-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .ds-stat .val {
            margin: 0;
            font-size: 26px;
            line-height: 1;
            font-weight: 700;
            color: var(--bs-body-color);
        }

        .ds-stat .ico {
            flex: none;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
        }

        .ds-stat .ico iconify-icon {
            font-size: 24px;
        }

        .ds-stat .note {
            margin: 0 10px 10px;
            padding: 6px 10px;
            border-radius: 8px;
            background: rgba(100, 116, 139, .08);
            font-size: 11.5px;
            line-height: 1.3;
            color: var(--bs-secondary-color, #6c757d);
        }

        @media (min-width: 768px) {
            .ds-stat .card-body {
                padding: 18px 18px 12px;
            }

            .ds-stat .lbl {
                min-height: 0;
                font-size: 13.5px;
            }

            .ds-stat .val {
                font-size: 30px;
            }

            .ds-stat .ico {
                width: 52px;
                height: 52px;
            }

            .ds-stat .ico iconify-icon {
                font-size: 28px;
            }

            .ds-stat .note {
                font-size: 12px;
            }
        }

        /* ---- panels: every card header has the same height and alignment ---- */
        .ds-panel {
            height: 100%;
            margin-bottom: 0;
        }

        .ds-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 62px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--bs-border-color, #e5e9f0);
        }

        .ds-head .ttl {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .ds-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 6px;
            background: var(--ds-teal-soft);
            color: var(--ds-teal);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .ds-link:hover {
            background: rgba(15, 118, 110, .18);
            color: var(--ds-teal-dark);
        }

        /* range switch (7D / 14D / 30D) */
        .ds-seg {
            display: inline-flex;
            padding: 3px;
            border-radius: 8px;
            background: rgba(100, 116, 139, .12);
        }

        .ds-seg button {
            border: 0;
            background: transparent;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ds-slate);
        }

        .ds-seg button.active {
            background: var(--ds-teal);
            color: #fff;
        }

        /* ---- tables ---- */
        .ds-table th {
            padding: .6rem 1.25rem;
            border: 0;
            background: rgba(100, 116, 139, .07);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748b;
            white-space: nowrap;
        }

        .ds-table td {
            padding: .7rem 1.25rem;
            vertical-align: middle;
        }

        .ds-player {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .ds-player img {
            flex: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .ds-player .nm {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.25;
        }

        .ds-player .sub {
            font-size: 12px;
            color: var(--bs-secondary-color, #6c757d);
            line-height: 1.25;
        }

        .ds-time {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            background: rgba(2, 132, 199, .10);
            color: #0369a1;
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* action icons: no background */
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

        .empty-row {
            padding: 28px 12px !important;
            text-align: center;
            color: var(--bs-secondary-color, #6c757d);
        }

        @media (max-width: 575.98px) {
            .ds-head {
                padding: 10px 14px;
            }

            .ds-table th,
            .ds-table td {
                padding-left: .85rem;
                padding-right: .85rem;
            }
        }
        /* Today's practice strip */
        .ds-practice {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 1rem;
            border-radius: 10px;
            border: 1px solid var(--ds-line);
            background: rgba(100, 116, 139, .06);
        }

        .ds-practice.on {
            border-color: rgba(15, 118, 110, .35);
            background: var(--ds-teal-soft);
        }

        .ds-practice > iconify-icon {
            font-size: 28px;
            color: var(--ds-slate);
        }

        .ds-practice.on > iconify-icon {
            color: var(--ds-teal);
        }

        .ds-practice strong {
            display: block;
            font-size: 15px;
        }

        .ds-practice span {
            font-size: 13px;
            color: #64748b;
        }
    </style>

    <div class="ds">

        
        <div class="ds-actions">
            <a href="<?php echo e(route('admin.attendance.scan')); ?>" class="btn btn-teal">
                <iconify-icon icon="solar:qr-code-bold-duotone"></iconify-icon> Scan Attendance
            </a>
            <a href="<?php echo e(route('admin.players.create')); ?>" class="btn btn-teal-outline">
                <iconify-icon icon="solar:user-plus-bold-duotone"></iconify-icon> Add Player
            </a>
            <a href="<?php echo e(route('admin.players.index')); ?>" class="btn btn-slate-outline">
                <iconify-icon icon="solar:wallet-bold-duotone"></iconify-icon> Receive Payment
            </a>
        </div>

        
        <div class="ds-practice <?php echo e($practice ? 'on' : ''); ?>">
            <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
            <div class="flex-grow-1">
                <?php if($practice): ?>
                    <strong>Today: <?php echo e($practice->label); ?></strong>
                    <span><?php echo e($practice->time_text); ?><?php echo e($practice->is_moved ? ' (moved from ' . $practice->planned_date->format('D d M') . ')' : ''); ?>

                        &middot; <?php echo e($stats->today_count); ?> of <?php echo e($expected); ?> players marked</span>
                <?php elseif($next): ?>
                    <strong>No practice today</strong>
                    <span>Next: <?php echo e($next->label); ?>, <?php echo e($next->session_date->format('l d M')); ?> <?php echo e($next->time_text ? '- ' . $next->time_text : ''); ?></span>
                <?php else: ?>
                    <strong>No practice scheduled</strong>
                <?php endif; ?>
            </div>
            <?php if($isAdmin): ?>
                <a href="<?php echo e(route('admin.schedule.index')); ?>" class="ds-link">Schedule</a>
            <?php endif; ?>
        </div>

        
        <div class="row g-2 g-md-3 mb-3 row-cols-2 <?php echo e($isAdmin ? 'row-cols-xl-5' : 'row-cols-xl-4'); ?>">
            <div class="col">
                <a href="<?php echo e(route('admin.players.index')); ?>" class="card ds-stat">
                    <div class="card-body">
                        <p class="lbl">Active Players</p>
                        <div class="val-row">
                            <h3 class="val"><?php echo e(number_format($stats->active_players)); ?></h3>
                            <span class="ico" style="background:#0d94881a;color:#0d9488"><iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon></span>
                        </div>
                    </div>
                    <p class="note"><span class="text-success fw-semibold">+<?php echo e($stats->new_players); ?></span> joined this month</p>
                </a>
            </div>

            <div class="col">
                <a href="<?php echo e(route('admin.attendance.scan')); ?>" class="card ds-stat">
                    <div class="card-body">
                        <p class="lbl">Today's Attendance</p>
                        <div class="val-row">
                            <h3 class="val"><?php echo e(number_format($stats->today_count)); ?></h3>
                            <span class="ico" style="background:#0284c71a;color:#0284c7"><iconify-icon icon="solar:qr-code-bold-duotone"></iconify-icon></span>
                        </div>
                    </div>
                    <p class="note">
                        <span class="<?php echo e($dayDiff >= 0 ? 'text-success' : 'text-danger'); ?> fw-semibold"><?php echo e($dayDiff >= 0 ? '+' : '-'); ?><?php echo e(abs($dayDiff)); ?></span>
                        vs yesterday (<?php echo e($stats->yesterday_count); ?>)
                    </p>
                </a>
            </div>

            <div class="col">
                <a href="<?php echo e(route('admin.reminders.index')); ?>" class="card ds-stat">
                    <div class="card-body">
                        <p class="lbl">Reminders to Send</p>
                        <div class="val-row">
                            <h3 class="val"><?php echo e(number_format($stats->reminders)); ?></h3>
                            <span class="ico" style="background:#d977061a;color:#d97706"><iconify-icon icon="solar:bell-bold-duotone"></iconify-icon></span>
                        </div>
                    </div>
                    <p class="note"><?php echo e($stats->reminders ? 'Tap to send on WhatsApp' : 'All caught up'); ?></p>
                </a>
            </div>

            <div class="col">
                <div class="card ds-stat">
                    <div class="card-body">
                        <p class="lbl">Payment Due</p>
                        <div class="val-row">
                            <h3 class="val"><?php echo e(number_format($stats->payment_due)); ?></h3>
                            <span class="ico" style="background:#e11d481a;color:#e11d48"><iconify-icon icon="solar:danger-circle-bold-duotone"></iconify-icon></span>
                        </div>
                    </div>
                    <p class="note"><?php echo e($stats->payment_due ? 'Played more than paid for' : 'Everyone is paid up'); ?></p>
                </div>
            </div>

            <?php if($isAdmin): ?>
                <div class="col-12 col-xl">
                    <a href="<?php echo e(route('admin.payments.index')); ?>" class="card ds-stat">
                        <div class="card-body">
                            <p class="lbl">This Month (LKR)</p>
                            <div class="val-row">
                                <h3 class="val"><?php echo e(number_format($stats->month_revenue)); ?></h3>
                                <span class="ico" style="background:#4f46e51a;color:#4f46e5"><iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon></span>
                            </div>
                        </div>
                        <p class="note">
                            <?php if($revDelta === null): ?>
                                No payments last month
                            <?php else: ?>
                                <span class="<?php echo e($revDelta >= 0 ? 'text-success' : 'text-danger'); ?> fw-semibold"><?php echo e($revDelta >= 0 ? '+' : '-'); ?><?php echo e(abs($revDelta)); ?>%</span> vs last month
                            <?php endif; ?>
                        </p>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card ds-panel">
                    <div class="ds-head">
                        <h4 class="ttl">Attendance</h4>
                        <div class="ds-seg" id="rangeBtns">
                            <button type="button" data-days="7">7D</button>
                            <button type="button" class="active" data-days="14">14D</button>
                            <button type="button" data-days="30">30D</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div dir="ltr">
                            <div id="attChart" class="apex-charts"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card ds-panel">
                    <div class="ds-head">
                        <h4 class="ttl">Arrived Today</h4>
                        <a href="<?php echo e(route('admin.attendance.scan')); ?>" class="ds-link">
                            <iconify-icon icon="solar:qr-code-bold-duotone"></iconify-icon> Open scanner
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table ds-table mb-0">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th class="text-end">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <div class="ds-player">
                                                <img src="<?php echo e($r->photo_path ? asset($r->photo_path) : $ph); ?>" alt="">
                                                <div>
                                                    <div class="nm"><?php echo e($r->name); ?></div>
                                                    <div class="sub"><?php echo e($r->player_no); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><span class="ds-time"><?php echo e(\Carbon\Carbon::parse($r->created_at)->format('h:i A')); ?></span></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="2" class="empty-row">No one has arrived yet today.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card ds-panel">
                    <div class="ds-head">
                        <h4 class="ttl">Reminders to Send</h4>
                        <a href="<?php echo e(route('admin.reminders.index')); ?>" class="ds-link">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table ds-table mb-0">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Guardian</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $reminders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <div class="ds-player">
                                                <img src="<?php echo e($p->photo_url); ?>" alt="">
                                                <div>
                                                    <div class="nm"><?php echo e($p->name); ?></div>
                                                    <div class="sub"><?php echo e($p->player_no); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <div class="nm" style="font-size:14px"><?php echo e($p->guardian_name); ?></div>
                                            <div class="text-muted" style="font-size:12px"><?php echo e($p->guardian_phone); ?></div>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <?php if($p->whatsappLink()): ?>
                                                <a href="<?php echo e($p->whatsappLink()); ?>" target="_blank" rel="noopener"
                                                    class="act-ico" title="Send WhatsApp reminder">
                                                    <iconify-icon icon="logos:whatsapp-icon"></iconify-icon>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="3" class="empty-row">Nothing to send right now.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card ds-panel">
                    <div class="ds-head">
                        <h4 class="ttl">Payment Due</h4>
                        <a href="<?php echo e(route('admin.players.index')); ?>" class="ds-link">All players</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table ds-table mb-0">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th>Balance</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $dues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <div class="ds-player">
                                                <img src="<?php echo e($p->photo_url); ?>" alt="">
                                                <div>
                                                    <div class="nm"><?php echo e($p->name); ?></div>
                                                    <div class="sub"><?php echo e($p->player_no); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-sm-table-cell"><?php echo e($p->ageGroupBadge()); ?></td>
                                        <td data-balance="<?php echo e($p->id); ?>"><?php echo e($p->balanceBadge()); ?></td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="act-ico act-pay" title="Receive payment"
                                                onclick="openPayment(<?php echo e($p->id); ?>, <?php echo \Illuminate\Support\Js::from($p->name)->toHtml() ?>)">
                                                <iconify-icon icon="solar:wallet-bold-duotone"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="4" class="empty-row">Everyone is paid up.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('players._payment-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        (function() {
            var all = <?php echo json_encode($chart, 15, 512) ?>;
            var chart = null;

            function slice(n) {
                return {
                    labels: all.labels.slice(-n),
                    values: all.values.slice(-n)
                };
            }

            function draw(n) {
                var d = slice(n);
                var primary = '#0d9488';
                var small = window.innerWidth < 576;
                var opts = {
                    chart: {
                        type: 'bar',
                        height: small ? 250 : 300,
                        toolbar: {
                            show: false
                        }
                    },
                    series: [{
                        name: 'Players',
                        data: d.values
                    }],
                    xaxis: {
                        categories: d.labels,
                        tickAmount: small ? Math.min(d.labels.length, 7) : undefined,
                        tooltip: {
                            enabled: false
                        },
                        labels: {
                            rotate: 0,
                            hideOverlappingLabels: true,
                            trim: false,
                            style: {
                                fontSize: small ? '10px' : '12px'
                            },
                            // phones: show just the day number (01, 02 ...); the tooltip still shows the full date
                            formatter: function(v) {
                                return small && typeof v === 'string' ? v.split(' ')[0] : v;
                            }
                        }
                    },
                    tooltip: {
                        x: {
                            formatter: function(v, o) {
                                return d.labels[o.dataPointIndex];
                            }
                        }
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: {
                            formatter: function(v) {
                                return Math.round(v);
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            columnWidth: small ? '65%' : '55%'
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    grid: {
                        borderColor: 'rgba(100,116,139,.18)',
                        strokeDashArray: 4
                    },
                    colors: [primary]
                };
                if (chart) {
                    chart.updateOptions({
                        series: opts.series,
                        xaxis: opts.xaxis
                    });
                } else {
                    chart = new ApexCharts(document.querySelector('#attChart'), opts);
                    chart.render();
                }
            }

            function start() {
                draw(14);
                document.querySelectorAll('#rangeBtns button').forEach(function(b) {
                    b.addEventListener('click', function() {
                        document.querySelectorAll('#rangeBtns button').forEach(function(x) {
                            x.classList.remove('active');
                        });
                        b.classList.add('active');
                        draw(parseInt(b.dataset.days, 10));
                    });
                });
            }

            // wait for the page (and the theme's own scripts) before deciding whether to load ApexCharts
            window.addEventListener('load', function() {
                if (typeof ApexCharts !== 'undefined') return start();
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js';
                s.onload = start;
                document.head.appendChild(s);
            });
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/dashboard.blade.php ENDPATH**/ ?>