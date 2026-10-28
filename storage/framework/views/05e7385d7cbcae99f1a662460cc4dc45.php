<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Practice Schedule', 'subtitle' => $view === 'past' ? 'Past' : 'Upcoming'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        /* ---------- calendar ---------- */
        .cal-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cal-title {
            min-width: 130px;
            text-align: center;
        }

        .cal-quick {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .cal-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 12px;
            color: #64748b;
        }

        .cal-legend i {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: -1px;
        }

        .cal-legend i.sel {
            border: 2px solid #0f766e;
            background: rgba(15, 118, 110, .12);
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
        }

        .cal-wd {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            padding: 4px 0;
        }

        .cal-day {
            position: relative;
            min-height: 84px;
            padding: 6px;
            border: 1.5px solid rgba(100, 116, 139, .18);
            border-radius: 10px;
            background: var(--bs-card-bg, #fff);
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
            transition: border-color .12s ease, background .12s ease;
        }

        .cal-day:hover {
            border-color: rgba(15, 118, 110, .5);
        }

        .cal-day.out {
            background: transparent;
            border-style: dashed;
            opacity: .35;
            cursor: default;
        }

        .cal-day.past {
            background: rgba(100, 116, 139, .05);
        }

        .cal-day.past .n {
            color: #94a3b8;
        }

        .cal-day.today {
            box-shadow: inset 0 0 0 2px rgba(15, 118, 110, .35);
        }

        .cal-day .n {
            font-weight: 600;
            font-size: 13px;
            color: #334155;
        }

        .cal-day .chip {
            display: flex;
            flex-direction: column;
            border-radius: 6px;
            padding: 3px 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.25;
            background: color-mix(in srgb, var(--c) 12%, transparent);
            color: var(--c);
        }

        .cal-day .chip .t {
            font-weight: 500;
            opacity: .85;
        }

        .cal-day .chip.off {
            background: rgba(148, 163, 184, .18);
            color: #64748b;
            text-decoration: line-through;
        }

        .cal-day .cnt {
            font-size: 10.5px;
            color: #64748b;
        }

        .cal-day .tick {
            position: absolute;
            top: 4px;
            right: 5px;
            color: #0f766e;
            font-size: 18px;
            display: none;
        }

        .cal-day.sel {
            border-color: #0f766e;
            background: rgba(15, 118, 110, .08);
        }

        .cal-day.sel .tick {
            display: block;
        }

        .cal-bar {
            position: sticky;
            bottom: 10px;
            z-index: 3;
            margin-top: 14px;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid rgba(15, 118, 110, .35);
            background: var(--bs-card-bg, #fff);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        }

        .cal-sel {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .grp-pick {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .grp-pick input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .grp-pick label {
            margin: 0;
            padding: 7px 12px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
        }

        .grp-pick input:checked + label {
            border-color: var(--c);
            background: color-mix(in srgb, var(--c) 10%, transparent);
            color: var(--c);
        }

        .grp-pick input:focus-visible + label {
            outline: 2px solid var(--c);
            outline-offset: 2px;
        }

        /* phones: compact cells, colour bar instead of text */
        @media (max-width: 575.98px) {
            .cal-grid {
                gap: 3px;
            }

            .cal-day {
                min-height: 54px;
                padding: 4px 3px;
                border-radius: 8px;
            }

            .cal-day .chip {
                padding: 0;
                height: 6px;
                border-radius: 3px;
                background: var(--c);
            }

            .cal-day .chip.off {
                background: #94a3b8;
            }

            .cal-day .chip .full,
            .cal-day .chip .t,
            .cal-day .cnt {
                display: none;
            }

            .cal-day .tick {
                font-size: 14px;
                top: 2px;
                right: 2px;
            }

            .cal-title {
                min-width: 100px;
            }
        }

        .sc-tabs .nav-link {
            font-weight: 600;
        }

        .sc-month {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #64748b;
            margin: 18px 0 8px;
        }

        .sc-month:first-child {
            margin-top: 0;
        }

        .sc-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border: 1px solid rgba(100, 116, 139, .2);
            border-radius: 10px;
            margin-bottom: 8px;
            transition: border-color .12s ease;
        }

        .sc-row:hover {
            border-color: rgba(15, 118, 110, .45);
        }

        .sc-row.today {
            border-color: #0f766e;
            background: rgba(15, 118, 110, .05);
        }

        .sc-row.off {
            background: rgba(100, 116, 139, .05);
        }

        .sc-row.off .sc-info strong,
        .sc-row.off .sc-date {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .sc-date {
            flex: 0 0 56px;
            text-align: center;
            line-height: 1.1;
            color: #0f766e;
        }

        .sc-date .w {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sc-date .d {
            font-size: 24px;
            font-weight: 700;
        }

        .sc-date .m {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
        }

        .sc-info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .sc-info strong {
            font-size: 15px;
        }

        .sc-info .meta {
            font-size: 13px;
            color: #64748b;
        }

        .sc-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 5px;
            margin-left: 4px;
            vertical-align: middle;
        }

        .sc-count {
            flex: 0 0 110px;
            text-align: right;
            font-size: 13px;
            color: #64748b;
        }

        .sc-count b {
            display: block;
            font-size: 16px;
            color: #334155;
        }

        .sc-act {
            flex: 0 0 auto;
            white-space: nowrap;
        }








        @media (max-width: 575.98px) {
            .sc-row {
                flex-wrap: wrap;
                gap: 10px;
            }

            .sc-info {
                flex: 1 1 calc(100% - 70px);
            }

            .sc-count {
                flex: 1 1 auto;
                text-align: left;
                padding-left: 70px;
            }

            .sc-count b {
                display: inline;
                font-size: 14px;
            }
        }
    </style>

    <?php
        $groupColor = ['fri' => '#2563eb', 'sun' => '#0f766e', 'all' => '#b45309'];
        $groupShort = ['fri' => 'Fri players', 'sun' => 'Sun players', 'all' => 'All players'];
        $todayKey = today()->toDateString();
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
    ?>

    
    <div class="card" id="calCard">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">Practice Calendar</h5>
                    <p class="card-subtitle mb-0">Tap the days, choose who the practice is for, then press <strong>Add
                            practice</strong> once. Tap existing days to cancel or remove them.</p>
                </div>
                <div class="cal-nav">
                    <a href="<?php echo e(route('admin.schedule.index', ['month' => $prevMonth, 'view' => $view])); ?>" class="btn btn-light btn-sm"
                        aria-label="Previous month"><iconify-icon icon="solar:alt-arrow-left-outline"></iconify-icon></a>
                    <strong class="cal-title"><?php echo e($month->format('F Y')); ?></strong>
                    <a href="<?php echo e(route('admin.schedule.index', ['month' => $nextMonth, 'view' => $view])); ?>" class="btn btn-light btn-sm"
                        aria-label="Next month"><iconify-icon icon="solar:alt-arrow-right-outline"></iconify-icon></a>
                </div>
            </div>
            <div class="cal-quick mt-3">
                <span class="text-muted small me-1">Quick select:</span>
                <button type="button" class="btn btn-sm btn-soft-primary" data-pick-weekday="5">All Fridays</button>
                <button type="button" class="btn btn-sm btn-soft-primary" data-pick-weekday="0">All Sundays</button>
                <button type="button" class="btn btn-sm btn-soft-secondary" data-pick-weekday="56">Fridays + Sundays</button>
                <button type="button" class="btn btn-sm btn-light" id="calClear">Clear</button>
            </div>
        </div>

        <div class="card-body">
            <div class="cal-legend mb-2">
                <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span><i style="background: <?php echo e($groupColor[$key] ?? '#475569'); ?>"></i><?php echo e($label); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <span><i style="background:#94a3b8"></i>Cancelled</span>
                <span><i class="sel"></i>Selected</span>
            </div>

            <div class="cal-grid" id="calGrid">
                <?php $__currentLoopData = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="cal-wd"><?php echo e($wd); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php for($d = $gridFrom->copy(); $d->lte($gridTo); $d->addDay()): ?>
                    <?php
                        $key = $d->toDateString();
                        $daySessions = $inGrid[$key] ?? collect();
                        $act = $daySessions->firstWhere('is_cancelled', false);
                        $can = $daySessions->firstWhere('is_cancelled', true);
                        $state = $act ? 'active' : ($can ? 'cancelled' : 'none');
                        $inMonth = $d->month === $month->month;
                        $cnt = $marked[$key] ?? 0;
                    ?>
                    <?php if(!$inMonth): ?>
                        <div class="cal-day out" aria-hidden="true"><span class="n"><?php echo e($d->day); ?></span></div>
                    <?php else: ?>
                        <button type="button" class="cal-day <?php echo e($key < $todayKey ? 'past' : ''); ?> <?php echo e($key === $todayKey ? 'today' : ''); ?> st-<?php echo e($state); ?>"
                            data-date="<?php echo e($key); ?>" data-weekday="<?php echo e($d->dayOfWeek); ?>" data-state="<?php echo e($state); ?>"
                            data-past="<?php echo e($key < $todayKey ? 1 : 0); ?>" data-marked="<?php echo e($cnt); ?>"
                            aria-pressed="false" aria-label="<?php echo e($d->format('l d F')); ?><?php echo e($act ? ', ' . $act->label : ($can ? ', cancelled' : '')); ?>">
                            <span class="n"><?php echo e($d->day); ?></span>
                            <?php if($act): ?>
                                <span class="chip" style="--c: <?php echo e($groupColor[$act->slot] ?? '#475569'); ?>">
                                    <span class="full"><?php echo e($groupShort[$act->slot] ?? $act->slot); ?></span>
                                    <span class="t"><?php echo e($act->start_time ? \Carbon\Carbon::parse($act->start_time)->format('g:i') : ''); ?></span>
                                </span>
                                <?php if($cnt): ?>
                                    <span class="cnt"><?php echo e($cnt); ?> marked</span>
                                <?php endif; ?>
                            <?php elseif($can): ?>
                                <span class="chip off"><span class="full">Cancelled</span></span>
                            <?php endif; ?>
                            <span class="tick"><iconify-icon icon="solar:check-circle-bold"></iconify-icon></span>
                        </button>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            
            <div class="cal-bar d-none" id="calBar">
                <div class="cal-sel"><strong id="calCount">0</strong> day(s) selected <span class="text-muted" id="calList"></span></div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small mb-1">Practice for</label>
                        <div class="grp-pick">
                            <input type="radio" name="calSlot" id="cs_auto" value="auto"
                                data-start="<?php echo e(\App\Models\PracticeSession::defaultTimes('fri')[0]); ?>"
                                data-end="<?php echo e(\App\Models\PracticeSession::defaultTimes('fri')[1]); ?>">
                            <label for="cs_auto" style="--c: #334155" title="Fridays for Friday players, Sundays for Sunday players">By day
                                (Fri / Sun players)</label>
                            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <input type="radio" name="calSlot" id="cs_<?php echo e($key); ?>" value="<?php echo e($key); ?>" <?php echo e($loop->last ? 'checked' : ''); ?>

                                    data-start="<?php echo e(\App\Models\PracticeSession::defaultTimes($key)[0]); ?>"
                                    data-end="<?php echo e(\App\Models\PracticeSession::defaultTimes($key)[1]); ?>">
                                <label for="cs_<?php echo e($key); ?>" style="--c: <?php echo e($groupColor[$key] ?? '#475569'); ?>"><?php echo e($label); ?></label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="calStart">Start</label>
                        <input type="time" class="form-control" id="calStart" value="16:00">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="calEnd">End</label>
                        <input type="time" class="form-control" id="calEnd" value="17:30">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1" for="calNote">Note <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" id="calNote" maxlength="150" placeholder="Ex: Main ground">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-primary ac-btn" data-bulk="add">
                        <iconify-icon icon="solar:add-circle-outline"></iconify-icon> Add practice
                    </button>
                    <button type="button" class="btn btn-soft-warning ac-btn" data-bulk="cancel">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Cancel days
                    </button>
                    <button type="button" class="btn btn-soft-success ac-btn" data-bulk="restore">
                        <iconify-icon icon="solar:restart-outline"></iconify-icon> Put back
                    </button>
                    <button type="button" class="btn btn-soft-danger ac-btn" data-bulk="remove">
                        <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon> Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Practice Days</h5>
                    <p class="card-subtitle mb-0">Every practice day in a list. Change the date or time of a single day,
                        cancel it or put it back.</p>
                </div>
                <button type="button" class="btn btn-primary" id="btnAdd">
                    <iconify-icon icon="solar:add-circle-outline" class="me-1"></iconify-icon> Add Practice
                </button>
            </div>

            <ul class="nav nav-pills sc-tabs">
                <li class="nav-item"><a class="nav-link <?php echo e($view === 'upcoming' ? 'active' : ''); ?>"
                        href="<?php echo e(route('admin.schedule.index')); ?>">Upcoming</a></li>
                <li class="nav-item"><a class="nav-link <?php echo e($view === 'past' ? 'active' : ''); ?>"
                        href="<?php echo e(route('admin.schedule.index', ['view' => 'past'])); ?>">Past</a></li>
            </ul>
        </div>

        <div class="card-body">
            <?php $lastMonth = null; ?>
            <?php $__empty_1 = true; $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $key = $s->session_date->toDateString();
                    $count = $marked[$key] ?? 0;
                    $exp = $expected[$s->slot] ?? 0;
                    $isToday = $s->session_date->isToday();
                    $isPast = $s->session_date->lt(today());
                    $locked = $count > 0;
                    $isAdded = false; // every day is picked by the admin now
                ?>

                <?php if($s->session_date->format('Y-m') !== $lastMonth): ?>
                    <?php $lastMonth = $s->session_date->format('Y-m'); ?>
                    <div class="sc-month"><?php echo e($s->session_date->format('F Y')); ?></div>
                <?php endif; ?>

                <div class="sc-row <?php echo e($isToday && !$s->is_cancelled ? 'today' : ''); ?> <?php echo e($s->is_cancelled ? 'off' : ''); ?>"
                    id="ps-<?php echo e($s->id); ?>">
                    <div class="sc-date">
                        <div class="w"><?php echo e($s->session_date->format('D')); ?></div>
                        <div class="d"><?php echo e($s->session_date->format('d')); ?></div>
                        <div class="m"><?php echo e($s->session_date->format('M')); ?></div>
                    </div>

                    <div class="sc-info">
                        <strong><?php echo e($s->label); ?></strong>
                        <?php if($isToday && !$s->is_cancelled): ?>
                            <span class="sc-tag" style="background:#0f766e1a;color:#0f766e">Today</span>
                        <?php endif; ?>
                        <?php if($s->is_cancelled): ?>
                            <span class="sc-tag" style="background:#4b55631a;color:#4b5563">Cancelled</span>
                        <?php elseif($s->is_moved): ?>
                            <span class="sc-tag" style="background:#b453091a;color:#b45309">Moved from
                                <?php echo e($s->planned_date->format('D d M')); ?></span>
                        <?php elseif($isAdded): ?>
                            <span class="sc-tag" style="background:#2563eb1a;color:#2563eb">Extra day</span>
                        <?php endif; ?>
                        <div class="meta">
                            <?php echo e($s->time_text ?: 'No time set'); ?>

                            <?php if($s->note): ?>
                                &middot; <?php echo e($s->note); ?>

                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sc-count">
                        <?php if($s->is_cancelled): ?>
                            <b>-</b> no practice
                        <?php elseif($isPast || $isToday): ?>
                            <b><?php echo e($count); ?> / <?php echo e($exp); ?></b> marked
                        <?php else: ?>
                            <b><?php echo e($exp); ?></b> players
                        <?php endif; ?>
                    </div>

                    <div class="sc-act ac-actions">
                        <?php if($locked): ?>
                            <span class="text-muted d-inline-flex align-items-center" title="Players already marked - the date is locked">
                                <iconify-icon icon="solar:lock-keyhole-minimalistic-outline" class="fs-18"></iconify-icon></span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-soft-primary btn-edit"
                            data-id="<?php echo e($s->id); ?>" data-date="<?php echo e($key); ?>"
                            data-start="<?php echo e($s->start_time ? substr($s->start_time, 0, 5) : ''); ?>"
                            data-end="<?php echo e($s->end_time ? substr($s->end_time, 0, 5) : ''); ?>" data-note="<?php echo e($s->note); ?>"
                            data-label="<?php echo e($s->label); ?>" data-locked="<?php echo e($locked ? 1 : 0); ?>">
                            <iconify-icon icon="solar:pen-outline"></iconify-icon><span><?php echo e($locked ? 'Time' : 'Change'); ?></span>
                        </button>
                        <?php if($s->is_cancelled): ?>
                            <button type="button" class="btn btn-sm btn-soft-success btn-restore" data-id="<?php echo e($s->id); ?>">
                                <iconify-icon icon="solar:restart-outline"></iconify-icon><span>Put back</span>
                            </button>
                        <?php elseif(!$locked): ?>
                            <button type="button" class="btn btn-sm btn-soft-danger btn-cancel" data-id="<?php echo e($s->id); ?>"
                                data-when="<?php echo e($s->session_date->format('l d M')); ?>">
                                <iconify-icon icon="solar:close-circle-outline"></iconify-icon><span>Cancel</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-center text-muted py-5 mb-0">No practice days in this period.</p>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="modal fade" id="psModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="psForm" method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="psTitle">Practice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="psMessage"></div>

                    <div class="mb-3" id="slotWrap">
                        <label class="form-label">Practice for</label>
                        <select name="slot" id="psSlot" class="form-select">
                            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" data-start="<?php echo e(\App\Models\PracticeSession::defaultTimes($key)[0]); ?>"
                                    data-end="<?php echo e(\App\Models\PracticeSession::defaultTimes($key)[1]); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">To add many days at once, use the calendar above.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="session_date" id="psDate" class="form-control" required>
                        <div class="form-text d-none" id="psLockHint">Players were already marked on this day, so the
                            date cannot change.</div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start</label>
                            <input type="time" name="start_time" id="psStart" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End</label>
                            <input type="time" name="end_time" id="psEnd" class="form-control">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Note <span class="text-muted">(optional)</span></label>
                        <input type="text" name="note" id="psNote" class="form-control" maxlength="150"
                            placeholder="Ex: Ground booked for a match">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var storeUrl = "<?php echo e(route('admin.schedule.store')); ?>";
            var updateTpl = "<?php echo e(route('admin.schedule.update', ['practice' => '__ID__'])); ?>";
            var cancelTpl = "<?php echo e(route('admin.schedule.cancel', ['practice' => '__ID__'])); ?>";
            var restoreTpl = "<?php echo e(route('admin.schedule.restore', ['practice' => '__ID__'])); ?>";
            var today = "<?php echo e(today()->toDateString()); ?>";

            var modalEl = document.getElementById('psModal');
            var form = document.getElementById('psForm');
            var msg = document.getElementById('psMessage');
            var f = {
                slot: document.getElementById('psSlot'),
                date: document.getElementById('psDate'),
                start: document.getElementById('psStart'),
                end: document.getElementById('psEnd'),
                note: document.getElementById('psNote')
            };

            function open(title, action, v, isNew) {
                document.getElementById('psTitle').textContent = title;
                form.setAttribute('action', action);
                msg.innerHTML = '';
                document.getElementById('slotWrap').classList.toggle('d-none', !isNew);
                f.slot.disabled = !isNew;
                // a locked date is sent unchanged (readonly, not disabled, so it is still posted)
                ACDate.set(f.date, v.date || null, {
                    min: isNew || v.locked !== '1' ? today : null,
                    locked: v.locked === '1'
                });
                document.getElementById('psLockHint').classList.toggle('d-none', v.locked !== '1');
                f.start.value = v.start || '';
                f.end.value = v.end || '';
                f.note.value = v.note || '';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            function slotTimes() {
                var o = f.slot.selectedOptions[0];
                f.start.value = o.dataset.start;
                f.end.value = o.dataset.end;
            }
            f.slot.addEventListener('change', slotTimes);

            document.getElementById('btnAdd').addEventListener('click', function() {
                open('Add Practice', storeUrl, {
                    date: ''
                }, true);
                slotTimes();
            });

            document.querySelectorAll('.btn-edit').forEach(function(b) {
                b.addEventListener('click', function() {
                    open('Edit ' + b.dataset.label, updateTpl.replace('__ID__', b.dataset.id), b.dataset, false);
                });
            });

            document.querySelectorAll('.btn-cancel').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var r = await Swal.fire({
                        title: 'Cancel practice on ' + b.dataset.when + '?',
                        text: 'No one can be marked present on this day. You can put it back later.',
                        input: 'text',
                        inputPlaceholder: 'Reason (optional), e.g. Rain',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonText: 'Keep it',
                        confirmButtonText: 'Yes, cancel'
                    });
                    if (!r.isConfirmed) return;
                    var d = await AC.call(cancelTpl.replace('__ID__', b.dataset.id), {
                        body: {
                            note: r.value || ''
                        }
                    });
                    done(d);
                });
            });

            document.querySelectorAll('.btn-restore').forEach(function(b) {
                b.addEventListener('click', async function() {
                    done(await AC.call(restoreTpl.replace('__ID__', b.dataset.id)));
                });
            });

            function done(d) {
                if (d.success) {
                    AC.toast(d.message);
                    setTimeout(function() {
                        location.reload();
                    }, 600);
                } else {
                    Swal.fire('Not possible', AC.errors(d), 'error');
                }
            }

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
    <script>
        // Practice calendar: tap days to select, then one action for all of them
        (function() {
            var bulkUrl = "<?php echo e(route('admin.schedule.bulk')); ?>";
            var grid = document.getElementById('calGrid');
            var bar = document.getElementById('calBar');
            var picked = new Set();
            var days = Array.prototype.slice.call(grid.querySelectorAll('.cal-day[data-date]'));
            var fmt = function(d) {
                var dt = new Date(d + 'T00:00:00');
                return dt.toLocaleDateString('en-GB', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });
            };

            function el(date) {
                return grid.querySelector('.cal-day[data-date="' + date + '"]');
            }

            function refresh() {
                days.forEach(function(d) {
                    var on = picked.has(d.dataset.date);
                    d.classList.toggle('sel', on);
                    d.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                var list = Array.from(picked).sort();
                bar.classList.toggle('d-none', list.length === 0);
                document.getElementById('calCount').textContent = list.length;
                document.getElementById('calList').textContent = list.length ?
                    '(' + list.slice(0, 6).map(fmt).join(', ') + (list.length > 6 ? ', ...' : '') + ')' : '';

                // which actions make sense for this selection
                var st = list.map(function(d) {
                    return el(d).dataset;
                });
                var has = function(f) {
                    return st.some(f);
                };
                setOn('add', has(function(x) {
                    return x.state !== 'active' && x.past === '0';
                }));
                setOn('cancel', has(function(x) {
                    return x.state === 'active' && x.marked === '0';
                }));
                setOn('restore', has(function(x) {
                    return x.state === 'cancelled';
                }));
                setOn('remove', has(function(x) {
                    return x.state !== 'none' && x.past === '0' && x.marked === '0';
                }));

                // suggest the group from the days picked (all Fridays -> Friday players, etc.)
                if (list.length) {
                    var wds = st.map(function(x) {
                        return x.weekday;
                    });
                    var allFri = wds.every(function(w) {
                        return w === '5';
                    });
                    var allSun = wds.every(function(w) {
                        return w === '0';
                    });
                    var friSun = wds.every(function(w) {
                        return w === '5' || w === '0';
                    });
                    // Fridays + Sundays together: each day for its own players
                    var slot = allFri ? 'fri' : (allSun ? 'sun' : (friSun ? 'auto' : 'all'));
                    var r = document.getElementById('cs_' + slot);
                    if (r && !bar.dataset.touched) {
                        r.checked = true;
                        setTimes(r);
                    }
                }
            }

            function setOn(action, on) {
                var b = bar.querySelector('[data-bulk="' + action + '"]');
                b.disabled = !on;
                b.classList.toggle('d-none', !on && action !== 'add');
            }

            function setTimes(r) {
                document.getElementById('calStart').value = r.dataset.start;
                document.getElementById('calEnd').value = r.dataset.end;
            }

            grid.addEventListener('click', function(e) {
                var d = e.target.closest('.cal-day[data-date]');
                if (!d) return;
                var k = d.dataset.date;
                picked.has(k) ? picked.delete(k) : picked.add(k);
                refresh();
            });

            // quick select: all Fridays / Sundays of this month from today on (56 = both)
            document.querySelectorAll('[data-pick-weekday]').forEach(function(b) {
                b.addEventListener('click', function() {
                    var want = b.dataset.pickWeekday === '56' ? ['5', '0'] : [b.dataset.pickWeekday];
                    days.forEach(function(d) {
                        if (want.indexOf(d.dataset.weekday) !== -1 && d.dataset.past === '0') picked.add(d.dataset.date);
                    });
                    delete bar.dataset.touched;
                    refresh();
                });
            });
            document.getElementById('calClear').addEventListener('click', function() {
                picked.clear();
                delete bar.dataset.touched;
                refresh();
            });

            document.querySelectorAll('input[name=calSlot]').forEach(function(r) {
                r.addEventListener('change', function() {
                    bar.dataset.touched = '1';
                    setTimes(r);
                });
            });

            bar.querySelectorAll('[data-bulk]').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var action = b.dataset.bulk;
                    var dates = Array.from(picked).sort();
                    if (!dates.length) return;

                    var ask = {
                        cancel: ['Cancel ' + dates.length + ' day(s)?', 'They stay on the calendar as "Cancelled" (no practice, no absences).', 'Yes, cancel'],
                        remove: ['Remove ' + dates.length + ' day(s)?', 'Use this for days added by mistake. They disappear from the calendar.', 'Yes, remove']
                    } [action];
                    if (ask && !(await AC.ask(ask[0], ask[1], ask[2]))) return;

                    var slot = (document.querySelector('input[name=calSlot]:checked') || {}).value;
                    b.disabled = true;
                    var d = await AC.call(bulkUrl, {
                        body: {
                            action: action,
                            dates: dates,
                            slot: slot,
                            start_time: document.getElementById('calStart').value,
                            end_time: document.getElementById('calEnd').value,
                            note: document.getElementById('calNote').value
                        }
                    });
                    b.disabled = false;
                    if (d.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Done',
                            text: d.message,
                            timer: d.message.indexOf('Skipped') === -1 ? 1600 : undefined,
                            showConfirmButton: d.message.indexOf('Skipped') !== -1
                        });
                        location.reload();
                    } else {
                        Swal.fire('Nothing changed', d.message || 'Something went wrong.', 'warning');
                    }
                });
            });

            refresh();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Practice Schedule'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/schedule/index.blade.php ENDPATH**/ ?>