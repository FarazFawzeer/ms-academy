<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Practice Schedule', 'subtitle' => $view === 'past' ? 'Past' : 'Upcoming'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
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

        .act-edit {
            color: #f59e0b;
        }

        .act-del {
            color: #dc2626;
        }

        .act-restore {
            color: #16a34a;
        }

        .act-lock {
            color: #94a3b8;
            cursor: default;
        }

        .act-lock:hover {
            transform: none;
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

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Practice Days</h5>
                    <p class="card-subtitle mb-0">
                        Regular:
                        <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo e($s['label']); ?> <?php echo e(\Carbon\Carbon::parse($s['start'])->format('g:i')); ?>-<?php echo e(\Carbon\Carbon::parse($s['end'])->format('g:i A')); ?><?php echo e($loop->last ? '.' : ','); ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        These are added automatically. Move, re-time or cancel any single day.
                    </p>
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
                    $isAdded = !$s->is_moved && $s->planned_date->dayOfWeek !== (int) ($slots[$s->slot]['weekday'] ?? -1);
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

                    <div class="sc-act">
                        <?php if($locked): ?>
                            <span class="act-ico act-lock" title="Players already marked - date is locked">
                                <iconify-icon icon="solar:lock-keyhole-minimalistic-bold-duotone"></iconify-icon></span>
                        <?php endif; ?>
                        <button type="button" class="act-ico act-edit btn-edit" title="Move / change time"
                            data-id="<?php echo e($s->id); ?>" data-date="<?php echo e($key); ?>"
                            data-start="<?php echo e($s->start_time ? substr($s->start_time, 0, 5) : ''); ?>"
                            data-end="<?php echo e($s->end_time ? substr($s->end_time, 0, 5) : ''); ?>" data-note="<?php echo e($s->note); ?>"
                            data-label="<?php echo e($s->label); ?>" data-locked="<?php echo e($locked ? 1 : 0); ?>">
                            <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                        </button>
                        <?php if($s->is_cancelled): ?>
                            <button type="button" class="act-ico act-restore btn-restore" title="Put back on"
                                data-id="<?php echo e($s->id); ?>">
                                <iconify-icon icon="solar:restart-circle-bold-duotone"></iconify-icon>
                            </button>
                        <?php elseif(!$locked): ?>
                            <button type="button" class="act-ico act-del btn-cancel" title="Cancel this practice"
                                data-id="<?php echo e($s->id); ?>" data-when="<?php echo e($s->session_date->format('l d M')); ?>">
                                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
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
                        <label class="form-label">For players who train on</label>
                        <select name="slot" id="psSlot" class="form-select">
                            <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" data-start="<?php echo e($s['start']); ?>" data-end="<?php echo e($s['end']); ?>">
                                    <?php echo e($s['label']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">Example: Sunday is off, so add a Saturday practice for the Sunday players.
                        </div>
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
                f.date.value = v.date || '';
                f.date.min = isNew || v.locked !== '1' ? today : '';
                // a locked date is sent unchanged (readonly, not disabled, so it is still posted)
                f.date.readOnly = v.locked === '1';
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Practice Schedule'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/schedule/index.blade.php ENDPATH**/ ?>