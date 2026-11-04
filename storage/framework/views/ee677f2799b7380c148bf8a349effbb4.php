<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'Register'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $prev = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->subMonth()->format('Y-m');
        $next = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->addMonth()->format('Y-m');
        $keep = array_filter(['group' => $group, 'days' => $days, 'sort' => $sort !== 'added' ? $sort : null]);
        $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin'], true);
        $todayKey = today()->toDateString();
        // the grid also holds the months around the chosen one: one header cell per month
        $monthGroups = [];
        $monthStart = [];
        foreach ($columns as $i => $c) {
            $mk = substr($c['key'], 0, 7);
            if (!$monthGroups || end($monthGroups)['key'] !== $mk) {
                $monthGroups[] = ['key' => $mk, 'label' => $c['date']->format('F Y'), 'n' => 0];
                $monthStart[$i] = true;
            }
            $monthGroups[count($monthGroups) - 1]['n']++;
        }
        $inMonthCount = collect($columns)->filter(fn($c) => $c['key'] >= $mFrom && $c['key'] <= $mTo)->count();
        $span = count($columns) + 3;
        $rateColour = fn($r) => $r === null ? '#6b7280' : ($r >= 80 ? '#15803d' : ($r >= 50 ? '#b45309' : '#b91c1c'));
        $lastGroup = null;
    ?>

    <style>
        /* Filters: right aligned, auto-apply */
        .rg-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
        }

        .rg-filters .form-control,
        .rg-filters .form-select {
            height: 38px;
            font-size: 14px;
        }

        .rg-filters .rg-sel {
            flex: 0 0 170px;
        }

        .rg-filters .rg-sort {
            flex: 0 0 185px;
        }

        .rg-month {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }

        .rg-month a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 38px;
            font-size: 20px;
            color: #475569;
            text-decoration: none;
        }

        .rg-month input {
            width: 160px;
        }

        @media (max-width: 767.98px) {
            .rg-filters {
                justify-content: stretch;
            }

            .rg-filters .rg-sel,
            .rg-filters .rg-sort {
                flex: 1 1 calc(50% - .25rem);
            }

            .rg-month {
                flex: 1 1 100%;
            }

            .rg-month input {
                flex: 1;
                width: auto;
            }

            /* phones: narrower name column so more practice days fit */
            .rg-wrap .rg .rg-name {
                min-width: 118px;
                max-width: 132px;
                white-space: normal;
                padding: 6px 8px;
                font-size: 12px;
            }

            .rg-wrap .rg .rg-col {
                min-width: 42px;
            }
        }

        /* Legend */
        .rg-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 12px;
            color: #64748b;
        }

        .rg-legend > span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .rg-legend .lg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 26px;
            border-radius: 4px;
            border: 1px solid #e7dcc0;
        }

        .rg-legend .lg .tk,
        .rg-legend .lg .abx {
            width: 18px;
            height: 18px;
            font-size: 11px;
        }

        .rg-legend .lg .abx {
            font-size: 8px;
        }

        /* Grid: frozen like Excel - names stay on the left, header on top, dates slide */
        .rg-wrap {
            overflow: auto;
            max-height: calc(100vh - 240px);
            min-height: 260px;
            border: 1px solid rgba(100, 116, 139, .2);
            border-radius: 10px;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .rg {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .rg th,
        .rg td {
            border-bottom: 1px solid rgba(100, 116, 139, .15);
            padding: 0;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .rg thead th {
            background: #f8fafc;
            font-weight: 600;
            padding: 8px 4px;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* month row on top, dates under it (both stay on top while scrolling down) */
        .rg thead tr.mrow th {
            height: 32px;
            padding: 4px 8px;
        }

        .rg thead tr.drow th {
            top: 32px;
        }

        .rg th.mh {
            text-align: left;
            font-size: 12.5px;
            border-bottom: 1px solid rgba(100, 116, 139, .2);
        }

        .rg th.mh a {
            color: #475569;
            text-decoration: none;
            position: sticky;
            left: 200px;
        }

        .rg th.mh.cur {
            background: #e0f2f1;
        }

        .rg th.mh.cur a {
            color: #0f766e;
        }

        .rg th.rg-col.other .n,
        .rg th.rg-col.other .d {
            opacity: .65;
        }

        .rg .m-start {
            border-left: 2px solid #94a3b8 !important;
        }

        .rg .rg-name {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--bs-card-bg, #fff);
            text-align: left;
            padding: 6px 12px;
            min-width: 190px;
            border-right: 1px solid rgba(100, 116, 139, .2);
        }

        .rg thead .rg-name {
            z-index: 3;
            background: #f8fafc;
        }

        .rg-name a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
        }

        .rg-name small {
            display: block;
            color: #94a3b8;
            font-weight: 400;
        }

        .rg-col {
            min-width: 50px;
            line-height: 1.15;
        }

        .rg-col .d {
            display: block;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
        }

        .rg-col .n {
            font-size: 14px;
        }

        .rg-col.cancel {
            color: #94a3b8;
        }

        .rg-col .tag {
            display: block;
            font-size: 9px;
            font-weight: 600;
            color: #b45309;
        }

        .rg-col.extra .tag {
            color: #2563eb;
        }

        .rg td.c {
            height: 42px;
        }

        .rg td.x {
            background: repeating-linear-gradient(45deg, #f1f5f9, #f1f5f9 4px, #e2e8f0 4px, #e2e8f0 8px);
        }

        .ok,
        .ex,
        .due {
            font-size: 22px;
            line-height: 1;
        }

        /* ---------- Excel look (client sheet): cream grid, filled cells ----------
           green fill = present & paid, red fill = present & payment pending, black = absent */
        .rg td.c {
            background: #fff8e1;
            border-right: 1px solid #e7dcc0;
            border-bottom: 1px solid #e7dcc0;
        }

        .rg td.c-P,
        .rg td.c-E {
            background: #bbf7d0;
        }

        .rg td.c-D,
        .rg td.c-G {
            background: #fca5a5;
        }

        .rg td.c-A {
            background: #111827;
        }

        .rg td.c-U {
            background: #4b5563;
        }

        .rg td.c-N {
            background: #fdf6e3;
        }

        /* extra day: small blue corner */
        .rg td.c-E,
        .rg td.c-G {
            background-image: linear-gradient(225deg, #2563eb 0 8px, transparent 8px);
        }

        .rg td.x,
        .rg td.c-X {
            background: repeating-linear-gradient(45deg, #f1f5f9, #f1f5f9 4px, #e2e8f0 4px, #e2e8f0 8px);
        }

        /* emoji-style marks: green square tick, red square AB */
        .tk,
        .abx {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 5px;
            color: #fff;
            font-weight: 800;
            line-height: 1;
        }

        .tk {
            background: #16a34a;
            font-size: 14px;
        }

        .abx {
            background: #dc2626;
            font-size: 9.5px;
            letter-spacing: -.2px;
        }

        .c-U .abx {
            background: #9ca3af;
        }

        .rg tbody tr:not(.grp):hover td.c-P,
        .rg tbody tr:not(.grp):hover td.c-E {
            background-color: #a7f3d0;
        }

        /* client colours: green = present & paid, red = present & payment pending, black = absent */
        .ok,
        .ex {
            color: #16a34a;
        }

        .due {
            color: #dc2626;
        }

        .ab {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 4px;
            background: #111827;
            color: #fff;
        }

        .ab-free {
            background: transparent;
            color: #6b7280;
            box-shadow: inset 0 0 0 1px #9ca3af;
        }

        .nm {
            color: #94a3b8;
            font-weight: 700;
        }

        .rg-name .added {
            font-size: 10.5px;
            color: #94a3b8;
        }

        /* edit mode (Admin): tap a cell to mark it */
        .rg.editing td[data-d] {
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(15, 118, 110, .12);
        }

        .rg.editing td[data-d]:hover {
            background: rgba(15, 118, 110, .10) !important;
        }

        .rg td.busy {
            opacity: .35;
        }

        .rg .rest {
            display: none;
            margin: 4px auto 0;
            font-size: 9.5px;
            font-weight: 700;
            line-height: 1;
            padding: 3px 5px;
            border-radius: 4px;
            border: 0;
            background: #111827;
            color: #fff;
        }

        .rg.editing .rest {
            display: block;
        }

        .rg-editbar {
            display: none !important;
        }

        .rg-editbar.on {
            display: flex !important;
        }

        .na {
            color: #cbd5e1;
            font-size: 16px;
        }

        .rg tr.grp td {
            white-space: nowrap;
            background: #f1f5f9;
            text-align: left;
            padding: 6px 12px;
            font-weight: 600;
            font-size: 12px;
            color: #475569;
        }

        .rg tr.grp td .rg-name {
            background: #f1f5f9;
        }

        .rg tbody tr:not(.grp):hover td:not(.c) {
            background: rgba(15, 118, 110, .04);
        }

        /* drag with the mouse to slide the dates */
        .rg-wrap.can-drag {
            cursor: grab;
        }

        .rg-wrap.dragging {
            cursor: grabbing;
            user-select: none;
        }

        .rg tbody tr:not(.grp):hover td.rg-name {
            background: #f0fdfa;
        }

        .rg tfoot td {
            background: #f8fafc;
            font-weight: 600;
            padding: 8px 4px;
            color: #475569;
        }

        .rg .sum {
            min-width: 76px;
            width: 76px;
            padding: 0 8px;
        }

        /* totals stay on the right on tablets and computers */
        @media (min-width: 768px) {
            .rg .sum {
                position: sticky;
                z-index: 1;
                background: var(--bs-card-bg, #fff);
            }

            .rg .sum.s1 {
                right: 76px;
                border-left: 1px solid rgba(100, 116, 139, .2);
            }

            .rg .sum.s2 {
                right: 0;
            }

            .rg thead .sum {
                z-index: 3;
                background: #f8fafc;
            }

            .rg tfoot .sum {
                background: #f8fafc;
            }
        }

        .rg tfoot td {
            position: sticky;
            bottom: 0;
        }

        .rg tfoot .rg-name {
            z-index: 3;
        }

        @media (max-width: 767.98px) {
            .rg th.mh a {
                left: 128px;
            }
        }

        .rate {
            display: inline-block;
            width: 52px;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 0;
            border-radius: 6px;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Attendance Register - <?php echo e($monthTx); ?></h5>
                    <p class="card-subtitle mb-0"><?php echo e(count($rows)); ?> active player(s) &middot; <?php echo e($inMonthCount); ?> day(s)
                        in <?php echo e($monthTx); ?>. Slide left / right for the months before and after.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if($isAdmin && count($columns)): ?>
                        <button type="button" class="btn btn-primary" id="rgEdit">
                            <iconify-icon icon="solar:pen-new-square-outline" class="me-1"></iconify-icon> Mark / edit
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo e(route('admin.attendance.register.export', array_filter(['month' => $month] + $keep))); ?>"
                        class="btn btn-outline-success">
                        <iconify-icon icon="solar:file-download-outline" class="me-1"></iconify-icon> Excel
                    </a>
                </div>
            </div>

            
            <form method="GET" id="rgFilter" class="rg-filters">
                <div class="rg-month">
                    <a href="<?php echo e(route('admin.attendance.register', ['month' => $prev] + $keep)); ?>" title="Previous month">
                        <iconify-icon icon="solar:alt-arrow-left-outline"></iconify-icon></a>
                    <input type="month" name="month" value="<?php echo e($month); ?>" class="form-control"
                        onchange="this.form.submit()">
                    <a href="<?php echo e(route('admin.attendance.register', ['month' => $next] + $keep)); ?>" title="Next month">
                        <iconify-icon icon="solar:alt-arrow-right-outline"></iconify-icon></a>
                </div>

                <select name="group" class="form-select rg-sel" onchange="this.form.submit()">
                    <option value="">All age groups</option>
                    <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($g); ?>" <?php echo e($group === $g ? 'selected' : ''); ?>><?php echo e($g); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>

                <select name="days" class="form-select rg-sel" onchange="this.form.submit()">
                    <option value="">All training days</option>
                    <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($key); ?>" <?php echo e($days === $key ? 'selected' : ''); ?>>Trains on <?php echo e($s['label']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <option value="both" <?php echo e($days === 'both' ? 'selected' : ''); ?>>Both days</option>
                </select>

                <select name="sort" class="form-select rg-sort" onchange="this.form.submit()" aria-label="Order players">
                    <option value="">First added first</option>
                    <option value="newest" <?php echo e($sort === 'newest' ? 'selected' : ''); ?>>Newest added first</option>
                    <option value="name" <?php echo e($sort === 'name' ? 'selected' : ''); ?>>Name A-Z</option>
                </select>
            </form>
        </div>

        <div class="card-body">
            <div class="rg-legend mb-3">
                <span><span class="lg" style="background:#bbf7d0"><span class="tk">&#10003;</span></span> Present &amp; paid</span>
                <span><span class="lg" style="background:#fca5a5"><span class="tk">&#10003;</span></span> Present, payment pending</span>
                <span><span class="lg" style="background:#111827"><span class="abx">AB</span></span> Absent</span>
                <span><span class="lg" style="background:#4b5563"><span class="abx" style="background:#9ca3af">AB</span></span> Absent, not counted</span>
                <span><span class="lg" style="background:#bbf7d0 linear-gradient(225deg,#2563eb 0 7px,transparent 7px)"><span class="tk">&#10003;</span></span> Extra day</span>
                <span><span class="lg" style="background:#fff8e1"><span class="nm">&ndash;</span></span> Not marked</span>
                <span><span class="lg" style="background:#fdf6e3"><span class="na">&middot;</span></span> Not their day</span>
            </div>

            <?php if($isAdmin): ?>
                <div class="alert alert-primary rg-editbar align-items-center flex-wrap gap-2 py-2" id="rgEditBar">
                    <div class="small" style="flex:1 1 280px">
                        <strong>Edit mode.</strong> Tap a box: empty &rarr; <strong>Present</strong> &rarr; <strong>Absent</strong>
                        &rarr; empty. Each tap is saved at once. <strong>Rest AB</strong> under a past date marks everyone
                        still empty that day absent.
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="rgDone">Done</button>
                </div>
            <?php endif; ?>

            <?php if(empty($columns)): ?>
                <p class="text-center text-muted py-5 mb-0">No practice days in <?php echo e($monthTx); ?>.</p>
            <?php else: ?>
                <div class="rg-wrap">
                    <table class="rg">
                        <thead>
                            <tr class="mrow">
                                <th class="rg-name" rowspan="2">Player</th>
                                <?php $__currentLoopData = $monthGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th colspan="<?php echo e($g['n']); ?>" class="mh m-start <?php echo e($g['key'] === $month ? 'cur' : ''); ?>">
                                        <a href="<?php echo e(route('admin.attendance.register', ['month' => $g['key']] + $keep)); ?>"><?php echo e($g['label']); ?></a>
                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <th class="sum s1" rowspan="2">Attended<small class="d-block fw-normal text-muted"><?php echo e(\Carbon\Carbon::parse($mFrom)->format('M')); ?></small></th>
                                <th class="sum s2" rowspan="2">Rate<small class="d-block fw-normal text-muted"><?php echo e(\Carbon\Carbon::parse($mFrom)->format('M')); ?></small></th>
                            </tr>
                            <tr class="drow">
                                <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ci => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $cls = in_array($c['type'], ['cancelled', 'none'], true) ? 'cancel' : ($c['type'] === 'extra' ? 'extra' : '');
                                        $tip = $c['type'] === 'none'
                                            ? 'No practice on the schedule'
                                            : ($c['type'] === 'cancelled'
                                            ? 'Practice cancelled' . ($c['session']->note ? ': ' . $c['session']->note : '')
                                            : ($c['type'] === 'extra'
                                                ? 'Extra day (marked by an Admin)'
                                                : $c['session']->label . ' ' . $c['session']->time_text . ($c['moved'] ? ' - moved from ' . $c['session']->planned_date->format('D d M') : '')));
                                    ?>
                                    <th class="rg-col <?php echo e($cls); ?> <?php echo e(isset($monthStart[$ci]) ? 'm-start' : ''); ?> <?php echo e($c['key'] >= $mFrom && $c['key'] <= $mTo ? 'cur' : 'other'); ?>"
                                        title="<?php echo e($tip); ?>" data-key="<?php echo e($c['key']); ?>">
                                        <span class="d"><?php echo e($c['date']->format('D')); ?></span>
                                        <span class="n"><?php echo e($c['date']->format('d')); ?></span>
                                        <?php if($c['type'] === 'none'): ?>
                                            <span class="tag">NO<br>PRACTICE</span>
                                        <?php elseif($c['type'] === 'cancelled'): ?>
                                            <span class="tag">OFF</span>
                                        <?php elseif($c['type'] === 'extra'): ?>
                                            <span class="tag">EXTRA</span>
                                        <?php elseif($c['moved']): ?>
                                            <span class="tag">MOVED</span>
                                        <?php endif; ?>
                                        <?php if($isAdmin && $c['type'] === 'session' && $c['key'] < $todayKey): ?>
                                            <button type="button" class="rest" data-rest="<?php echo e($c['key']); ?>"
                                                title="Mark everyone still empty on this day absent">Rest AB</button>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php if($r['group'] !== $lastGroup): ?>
                                    <?php $lastGroup = $r['group']; ?>
                                    <tr class="grp">
                                        <td class="rg-name">
                                            <?php echo e(\App\Models\Player::softBadge(\App\Support\AgeGroup::colour($r['group']), $r['group'] ?? '-', 64)); ?>

                                            <?php $n = collect($rows)->where('group', $r['group'])->count(); ?>
                                            <span class="ms-1"><?php echo e($n); ?> <?php echo e(\Illuminate\Support\Str::plural('player', $n)); ?></span>
                                        </td>
                                        <td colspan="<?php echo e($span - 1); ?>"></td>
                                    </tr>
                                <?php endif; ?>
                                <tr data-p="<?php echo e($r['player']->id); ?>">
                                    <td class="rg-name">
                                        <a href="<?php echo e(route('admin.players.show', $r['player']->id)); ?>"><?php echo e($r['player']->name); ?></a>
                                        <small><?php echo e($r['player']->player_no); ?> &middot; <?php echo e($r['player']->days_text); ?></small>
                                        <?php if($sort !== 'name' && $r['player']->created_at): ?>
                                            <small class="added">Added <?php echo e($r['player']->created_at->format('d M Y, g:i A')); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <?php $__currentLoopData = $r['cells']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $col = $columns[$i];
                                            // Admin can mark any past day or today (not a cancelled day, not the future)
                                            $edit = $isAdmin && !in_array($col['type'], ['cancelled', 'none'], true) && $col['key'] <= $todayKey
                                                ? 'data-d="' . $col['key'] . '" data-s="' . $cell . '" data-own="' . (in_array($cell, ['E', 'G', 'N'], true) ? 0 : 1) . '"'
                                                : '';
                                        ?>
                                        <?php
                                            $tick = '<span class="tk">&#10003;</span>';
                                            $abx = '<span class="abx">AB</span>';
                                            [$inner, $tip] = match ($cell) {
                                                'P' => [$tick, 'Present - paid'],
                                                'D' => [$tick, 'Present - payment pending'],
                                                'E' => [$tick, 'Extra day (not their training day) - paid'],
                                                'G' => [$tick, 'Extra day (not their training day) - payment pending'],
                                                'A' => [$abx, 'Absent'],
                                                'U' => [$abx, 'Absent - not counted, the package was already used up'],
                                                'T' => ['<span class="nm">&ndash;</span>', 'Today - not marked yet'],
                                                'M' => ['<span class="nm">&ndash;</span>', 'Not marked'],
                                                'N' => ['<span class="na">&middot;</span>', 'Not their training day'],
                                                'X' => ['', 'No practice'],
                                                default => ['', ''],
                                            };
                                        ?>
                                        <td class="c c-<?php echo e($cell); ?> <?php echo e(isset($monthStart[$i]) ? 'm-start' : ''); ?>" <?php echo $edit; ?> title="<?php echo e($tip); ?>"><?php echo $inner; ?></td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <td class="sum s1"><?php echo e($r['attended']); ?> <span class="text-muted">/ <?php echo e($r['expected']); ?></span></td>
                                    <td class="sum s2">
                                        <span class="rate"
                                            style="background:<?php echo e($rateColour($r['rate'])); ?>1a;color:<?php echo e($rateColour($r['rate'])); ?>">
                                            <?php echo e($r['rate'] === null ? '-' : $r['rate'] . '%'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="<?php echo e($span); ?>" class="text-center text-muted py-4">No players match these
                                        filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(count($rows)): ?>
                            <tfoot>
                                <tr>
                                    <td class="rg-name">Present</td>
                                    <?php $__currentLoopData = $present; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <td><?php echo e(in_array($columns[$i]['type'], ['cancelled', 'none'], true) ? '' : $n); ?></td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <td class="sum s1"></td>
                                    <td class="sum s2"></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">"Attended / expected" and Rate count <?php echo e($monthTx); ?> only. Rate = attended on their own
                    training days &divide; expected (practices held so far).</p>
            <?php endif; ?>
        </div>
    </div>
    <?php if($isAdmin && count($columns)): ?>
        <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <script>
            // Admin edit mode: tap cells to mark old or missed days, "Rest AB" fills the absences of a day
            (function() {
                var table = document.querySelector('.rg');
                var bar = document.getElementById('rgEditBar');
                var editBtn = document.getElementById('rgEdit');
                var markTpl = "<?php echo e(route('admin.players.day', ['player' => '__ID__'])); ?>";
                var restUrl = "<?php echo e(route('admin.attendance.register.rest-absent')); ?>";
                var changed = false;
                if (!table) return;

                function setEditing(on) {
                    table.classList.toggle('editing', on);
                    bar.classList.toggle('on', on);
                    editBtn.classList.toggle('d-none', on);
                    try {
                        sessionStorage.setItem('rg-edit', on ? '1' : '');
                    } catch (e) {}
                }
                editBtn.addEventListener('click', function() {
                    setEditing(true);
                });
                // Done: reload so the colours (paid / pending) and the totals are worked out again
                document.getElementById('rgDone').addEventListener('click', function() {
                    setEditing(false);
                    if (changed) location.reload();
                });
                try {
                    if (sessionStorage.getItem('rg-edit') === '1') setEditing(true);
                } catch (e) {}

                // empty -> present -> absent -> empty
                function next(code) {
                    if (['P', 'D', 'E', 'G'].indexOf(code) !== -1) return 'absent';
                    if (code === 'A' || code === 'U') return 'clear';
                    return 'present';
                }

                table.addEventListener('click', async function(e) {
                    if (!table.classList.contains('editing') || window.rgJustDragged) return;
                    var td = e.target.closest('td[data-d]');
                    if (!td || td.classList.contains('busy')) return;
                    e.preventDefault();

                    var status = next(td.dataset.s);
                    var own = td.dataset.own === '1';
                    td.classList.add('busy');
                    var d = await AC.call(markTpl.replace('__ID__', td.closest('tr').dataset.p), {
                        body: {
                            date: td.dataset.d,
                            status: status
                        }
                    });
                    td.classList.remove('busy');
                    if (!d.success) {
                        Swal.fire('Not saved', d.message || 'Something went wrong.', 'error');
                        return;
                    }
                    changed = true;
                    if (status === 'present') {
                        var due = d.balance < 0;
                        td.dataset.s = own ? (due ? 'D' : 'P') : (due ? 'G' : 'E');
                        td.innerHTML = '<span class="tk">&#10003;</span>';
                    } else if (status === 'absent') {
                        td.dataset.s = 'A';
                        td.innerHTML = '<span class="abx">AB</span>';
                    } else {
                        td.dataset.s = own ? 'M' : 'N';
                        td.innerHTML = own ? '<span class="nm">&ndash;</span>' : '<span class="na">&middot;</span>';
                    }
                    td.className = 'c c-' + td.dataset.s;
                });

                document.querySelectorAll('[data-rest]').forEach(function(b) {
                    b.addEventListener('click', async function(e) {
                        e.stopPropagation();
                        if (!(await AC.ask('Mark the rest absent?',
                                'Everyone who trains on this day and is still empty is marked absent. Absent uses a session while they have sessions left.',
                                'Yes, mark absent'))) return;
                        var d = await AC.call(restUrl, {
                            body: {
                                date: b.dataset.rest
                            }
                        });
                        if (d.success) {
                            AC.toast(d.message);
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        } else {
                            Swal.fire('Nothing changed', d.message || 'Something went wrong.', 'warning');
                        }
                    });
                });
            })();
        </script>
    <?php endif; ?>
    <script>
        // Hold the mouse button and drag to slide the dates left / right (phones and tablets swipe as usual)
        (function() {
            var wrap = document.querySelector('.rg-wrap');
            if (!wrap) return;

            // the grid holds the months around the chosen one: start at the chosen month
            var first = wrap.querySelector('th.rg-col.cur');
            var nameCol = wrap.querySelector('thead .rg-name');
            if (first && nameCol) wrap.scrollLeft += first.getBoundingClientRect().left - nameCol.getBoundingClientRect().right;
            var down = false,
                moved = false,
                sx = 0,
                sy = 0,
                sl = 0,
                st = 0;

            function fits() {
                wrap.classList.toggle('can-drag', wrap.scrollWidth > wrap.clientWidth || wrap.scrollHeight > wrap.clientHeight);
            }
            fits();
            window.addEventListener('resize', fits);

            wrap.addEventListener('mousedown', function(e) {
                if (e.button !== 0 || e.target.closest('a, button, input, select')) return;
                down = true;
                moved = false;
                sx = e.clientX;
                sy = e.clientY;
                sl = wrap.scrollLeft;
                st = wrap.scrollTop;
            });
            window.addEventListener('mousemove', function(e) {
                if (!down) return;
                var dx = e.clientX - sx,
                    dy = e.clientY - sy;
                if (!moved && Math.abs(dx) + Math.abs(dy) < 6) return;
                moved = true;
                wrap.classList.add('dragging');
                wrap.scrollLeft = sl - dx;
                wrap.scrollTop = st - dy;
                e.preventDefault();
            });
            window.addEventListener('mouseup', function() {
                if (!down) return;
                down = false;
                wrap.classList.remove('dragging');
                // a drag is not a tap on a cell
                if (moved) {
                    window.rgJustDragged = true;
                    setTimeout(function() {
                        window.rgJustDragged = false;
                    }, 0);
                }
            });
            // a drag that ends on a link (player name) must not open it
            wrap.addEventListener('click', function(e) {
                if (window.rgJustDragged) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Attendance Register'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/attendance/register.blade.php ENDPATH**/ ?>