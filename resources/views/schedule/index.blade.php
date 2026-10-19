@extends('layouts.vertical', ['subtitle' => 'Practice Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Practice Schedule', 'subtitle' => $view === 'past' ? 'Past' : 'Upcoming'])
    @include('partials.js-helpers')

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
                        @foreach ($slots as $s)
                            {{ $s['label'] }} {{ \Carbon\Carbon::parse($s['start'])->format('g:i') }}-{{ \Carbon\Carbon::parse($s['end'])->format('g:i A') }}{{ $loop->last ? '.' : ',' }}
                        @endforeach
                        These are added automatically. Move, re-time or cancel any single day.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" id="btnAdd">
                    <iconify-icon icon="solar:add-circle-outline" class="me-1"></iconify-icon> Add Practice
                </button>
            </div>

            <ul class="nav nav-pills sc-tabs">
                <li class="nav-item"><a class="nav-link {{ $view === 'upcoming' ? 'active' : '' }}"
                        href="{{ route('admin.schedule.index') }}">Upcoming</a></li>
                <li class="nav-item"><a class="nav-link {{ $view === 'past' ? 'active' : '' }}"
                        href="{{ route('admin.schedule.index', ['view' => 'past']) }}">Past</a></li>
            </ul>
        </div>

        <div class="card-body">
            @php $lastMonth = null; @endphp
            @forelse ($sessions as $s)
                @php
                    $key = $s->session_date->toDateString();
                    $count = $marked[$key] ?? 0;
                    $exp = $expected[$s->slot] ?? 0;
                    $isToday = $s->session_date->isToday();
                    $isPast = $s->session_date->lt(today());
                    $locked = $count > 0;
                    $isAdded = !$s->is_moved && $s->planned_date->dayOfWeek !== (int) ($slots[$s->slot]['weekday'] ?? -1);
                @endphp

                @if ($s->session_date->format('Y-m') !== $lastMonth)
                    @php $lastMonth = $s->session_date->format('Y-m'); @endphp
                    <div class="sc-month">{{ $s->session_date->format('F Y') }}</div>
                @endif

                <div class="sc-row {{ $isToday && !$s->is_cancelled ? 'today' : '' }} {{ $s->is_cancelled ? 'off' : '' }}"
                    id="ps-{{ $s->id }}">
                    <div class="sc-date">
                        <div class="w">{{ $s->session_date->format('D') }}</div>
                        <div class="d">{{ $s->session_date->format('d') }}</div>
                        <div class="m">{{ $s->session_date->format('M') }}</div>
                    </div>

                    <div class="sc-info">
                        <strong>{{ $s->label }}</strong>
                        @if ($isToday && !$s->is_cancelled)
                            <span class="sc-tag" style="background:#0f766e1a;color:#0f766e">Today</span>
                        @endif
                        @if ($s->is_cancelled)
                            <span class="sc-tag" style="background:#4b55631a;color:#4b5563">Cancelled</span>
                        @elseif ($s->is_moved)
                            <span class="sc-tag" style="background:#b453091a;color:#b45309">Moved from
                                {{ $s->planned_date->format('D d M') }}</span>
                        @elseif ($isAdded)
                            <span class="sc-tag" style="background:#2563eb1a;color:#2563eb">Extra day</span>
                        @endif
                        <div class="meta">
                            {{ $s->time_text ?: 'No time set' }}
                            @if ($s->note)
                                &middot; {{ $s->note }}
                            @endif
                        </div>
                    </div>

                    <div class="sc-count">
                        @if ($s->is_cancelled)
                            <b>-</b> no practice
                        @elseif ($isPast || $isToday)
                            <b>{{ $count }} / {{ $exp }}</b> marked
                        @else
                            <b>{{ $exp }}</b> players
                        @endif
                    </div>

                    <div class="sc-act">
                        @if ($locked)
                            <span class="act-ico act-lock" title="Players already marked - date is locked">
                                <iconify-icon icon="solar:lock-keyhole-minimalistic-bold-duotone"></iconify-icon></span>
                        @endif
                        <button type="button" class="act-ico act-edit btn-edit" title="Move / change time"
                            data-id="{{ $s->id }}" data-date="{{ $key }}"
                            data-start="{{ $s->start_time ? substr($s->start_time, 0, 5) : '' }}"
                            data-end="{{ $s->end_time ? substr($s->end_time, 0, 5) : '' }}" data-note="{{ $s->note }}"
                            data-label="{{ $s->label }}" data-locked="{{ $locked ? 1 : 0 }}">
                            <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                        </button>
                        @if ($s->is_cancelled)
                            <button type="button" class="act-ico act-restore btn-restore" title="Put back on"
                                data-id="{{ $s->id }}">
                                <iconify-icon icon="solar:restart-circle-bold-duotone"></iconify-icon>
                            </button>
                        @elseif (!$locked)
                            <button type="button" class="act-ico act-del btn-cancel" title="Cancel this practice"
                                data-id="{{ $s->id }}" data-when="{{ $s->session_date->format('l d M') }}">
                                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-5 mb-0">No practice days in this period.</p>
            @endforelse
        </div>
    </div>

    {{-- Add / edit popup --}}
    <div class="modal fade" id="psModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="psForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="psTitle">Practice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="psMessage"></div>

                    <div class="mb-3" id="slotWrap">
                        <label class="form-label">For players who train on</label>
                        <select name="slot" id="psSlot" class="form-select">
                            @foreach ($slots as $key => $s)
                                <option value="{{ $key }}" data-start="{{ $s['start'] }}" data-end="{{ $s['end'] }}">
                                    {{ $s['label'] }}</option>
                            @endforeach
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
            var storeUrl = "{{ route('admin.schedule.store') }}";
            var updateTpl = "{{ route('admin.schedule.update', ['practice' => '__ID__']) }}";
            var cancelTpl = "{{ route('admin.schedule.cancel', ['practice' => '__ID__']) }}";
            var restoreTpl = "{{ route('admin.schedule.restore', ['practice' => '__ID__']) }}";
            var today = "{{ today()->toDateString() }}";

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
@endsection
