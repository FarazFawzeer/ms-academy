@extends('layouts.vertical', ['subtitle' => 'Print ID Cards'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'Print ID Cards'])

    <style>
        /* Mode tiles */
        .pr-modes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .pr-modes input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .pr-modes label {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 100%;
            padding: 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            user-select: none;
            transition: all .12s ease;
        }

        .pr-modes label:hover {
            border-color: #99d5cf;
        }

        .pr-modes iconify-icon {
            font-size: 30px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .pr-modes strong {
            display: block;
            font-size: 14px;
            color: #334155;
        }

        .pr-modes small {
            color: #94a3b8;
            font-size: 12px;
        }

        .pr-modes input:checked + label {
            border-color: #0f766e;
            background: rgba(15, 118, 110, .06);
        }

        .pr-modes input:checked + label iconify-icon,
        .pr-modes input:checked + label strong {
            color: #0f766e;
        }

        .pr-modes input:focus-visible + label {
            outline: 2px solid #0f766e;
            outline-offset: 2px;
        }

        @media (max-width: 991.98px) {
            .pr-modes {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Panels */
        .pr-panel {
            display: none;
            margin-top: 18px;
        }

        .pr-panel.show {
            display: block;
        }

        .pr-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }

        /* Group chips */
        .pr-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pr-chip {
            border: 1.5px solid #e2e8f0;
            background: transparent;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 600;
            color: #475569;
            min-width: 90px;
            text-align: left;
            line-height: 1.2;
        }

        .pr-chip small {
            display: block;
            font-weight: 400;
            font-size: 11px;
            color: #94a3b8;
        }

        .pr-chip.on {
            border-color: var(--c);
            background: color-mix(in srgb, var(--c) 9%, transparent);
            color: var(--c);
        }

        /* ID range */
        .pr-range {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .pr-range .input-group {
            width: 180px;
        }

        .pr-hint {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }

        /* Pick list */
        .pr-pickbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }

        .pr-pickbar .pr-search {
            flex: 1 1 240px;
            position: relative;
        }

        .pr-pickbar .pr-search iconify-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #8a94a6;
        }

        .pr-pickbar .pr-search input {
            padding-left: 34px;
        }

        .pr-pickbar select {
            flex: 0 0 150px;
        }

        .pr-list {
            max-height: 420px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .pr-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            margin: 0;
        }

        .pr-item:last-child {
            border-bottom: 0;
        }

        .pr-item:hover {
            background: #f8fafc;
        }

        .pr-item.on {
            background: rgba(15, 118, 110, .06);
        }

        .pr-item input {
            width: 18px;
            height: 18px;
            accent-color: #0f766e;
            flex-shrink: 0;
        }

        .pr-item img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
        }

        .pr-item .nm {
            font-weight: 600;
            font-size: 14px;
        }

        .pr-item .sub {
            font-size: 12px;
            color: #94a3b8;
        }

        .pr-off {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            background: #6b72801a;
            color: #4b5563;
            margin-left: 4px;
        }

        .pr-link {
            background: none;
            border: 0;
            padding: 0;
            font-size: 13px;
            font-weight: 600;
            color: #0f766e;
        }

        /* Summary bar */
        .pr-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            margin-top: 20px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            position: sticky;
            bottom: 10px;
            z-index: 2;
        }

        .pr-total b {
            font-size: 22px;
            color: #0f766e;
        }

        .pr-total span {
            font-size: 13px;
            color: #64748b;
        }

        .pr-bar .form-select {
            width: auto;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="card-title mb-0">Print ID Cards</h5>
                <p class="card-subtitle mb-0">Choose which cards to print. They open in a new tab, 10 cards per A4 sheet.</p>
            </div>
            <a href="{{ route('admin.players.index') }}" class="btn btn-light ac-btn">
                <iconify-icon icon="solar:arrow-left-outline"></iconify-icon> All Players
            </a>
        </div>

        <div class="card-body">
            {{-- 1. what to print --}}
            <div class="pr-modes">
                <input type="radio" name="mode" id="m-all" value="all" checked>
                <label for="m-all"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon>
                    <span><strong>All players</strong><small>Every active player</small></span></label>

                <input type="radio" name="mode" id="m-group" value="group">
                <label for="m-group"><iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                    <span><strong>Age group</strong><small>For example only U12</small></span></label>

                <input type="radio" name="mode" id="m-range" value="range">
                <label for="m-range"><iconify-icon icon="solar:sort-by-alphabet-bold-duotone"></iconify-icon>
                    <span><strong>ID range</strong><small>{{ $prefix }}-0010 to {{ $prefix }}-0025</small></span></label>

                <input type="radio" name="mode" id="m-pick" value="pick">
                <label for="m-pick"><iconify-icon icon="solar:checklist-minimalistic-bold-duotone"></iconify-icon>
                    <span><strong>Pick players</strong><small>Tick the ones you need</small></span></label>
            </div>

            {{-- 2a. age group --}}
            <div class="pr-panel" data-for="group">
                <div class="pr-label">Age group</div>
                <div class="pr-chips" id="groupChips">
                    @foreach ($groups as $g)
                        <button type="button" class="pr-chip" data-group="{{ $g }}"
                            style="--c: {{ \App\Support\AgeGroup::colour($g) }}">
                            {{ $g }}<small data-count="{{ $g }}">0 players</small>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- 2b. ID range --}}
            <div class="pr-panel" data-for="range">
                <div class="pr-label">Player ID range</div>
                <div class="pr-range">
                    <div class="input-group">
                        <span class="input-group-text">From {{ $prefix }}-</span>
                        <input type="number" min="1" class="form-control" id="rFrom" placeholder="0001" inputmode="numeric">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text">To {{ $prefix }}-</span>
                        <input type="number" min="1" class="form-control" id="rTo" placeholder="0025" inputmode="numeric">
                    </div>
                </div>
                <div class="pr-hint" id="rangeHint">Type the first and last player number. Leave one empty to go from
                    the start or to the end.</div>
            </div>

            {{-- shared filters for all / group / range --}}
            <div class="pr-panel show" data-for="all group range" id="sharedFilters">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-auto">
                        <select class="form-select" id="fDays">
                            <option value="">All training days</option>
                            @foreach ($slots as $key => $s)
                                <option value="{{ $key }}">Trains on {{ $s['label'] }}</option>
                            @endforeach
                            <option value="both">Both days</option>
                        </select>
                    </div>
                    <div class="col-sm-auto">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="fInactive">
                            <label class="form-check-label" for="fInactive">Include inactive players</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2c. pick players --}}
            <div class="pr-panel" data-for="pick">
                <div class="pr-pickbar">
                    <div class="pr-search">
                        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                        <input type="search" class="form-control" id="pSearch" placeholder="Search name or ID"
                            autocomplete="off">
                    </div>
                    <select class="form-select" id="pGroup">
                        <option value="">All age groups</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small" id="pShown"></span>
                    <span class="d-flex gap-3">
                        <button type="button" class="pr-link" id="pAll">Select all shown</button>
                        <button type="button" class="pr-link text-danger" id="pNone">Clear</button>
                    </span>
                </div>
                <div class="pr-list" id="pList"></div>
            </div>

            {{-- 3. summary + print --}}
            <div class="pr-bar">
                <div class="pr-total"><b id="tCount">0</b> <span id="tText">ID cards</span></div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select class="form-select" id="fSort" title="Card order">
                        <option value="name">Order by name</option>
                        <option value="no">Order by player ID</option>
                    </select>
                    <button type="button" class="btn btn-primary" id="btnPrint">
                        <iconify-icon icon="solar:printer-outline" class="me-1"></iconify-icon> Open &amp; Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var players = @json($players);
            var cardsUrl = "{{ route('admin.players.cards') }}";
            var both = @json(implode(',', array_keys($slots)));
            var pad = function(n) {
                return @json($prefix) + '-' + String(n).padStart(4, '0');
            };

            var state = {
                mode: 'all',
                group: null,
                picked: new Set()
            };
            var $ = function(id) {
                return document.getElementById(id);
            };

            /* ---------- filters (same rules as the server) ---------- */
            function daysOk(p, d) {
                if (!d) return true;
                if (d === 'both') return p.days === both;
                return (',' + p.days + ',').indexOf(',' + d + ',') !== -1;
            }

            function num(v) {
                var n = parseInt(String(v || '').replace(/\D+/g, ''), 10);
                return isNaN(n) ? null : n;
            }

            function matching() {
                if (state.mode === 'pick') return players.filter(function(p) {
                    return state.picked.has(p.id);
                });

                var d = $('fDays').value,
                    inactive = $('fInactive').checked;
                var from = num($('rFrom').value),
                    to = num($('rTo').value);
                if (from && to && from > to) {
                    var t = from;
                    from = to;
                    to = t;
                }

                return players.filter(function(p) {
                    if (!inactive && !p.active) return false;
                    if (!daysOk(p, d)) return false;
                    if (state.mode === 'group' && p.group !== state.group) return false;
                    if (state.mode === 'range') {
                        if (from && p.id < from) return false;
                        if (to && p.id > to) return false;
                    }
                    return true;
                });
            }

            /* ---------- summary ---------- */
            function refresh() {
                var list = matching(),
                    n = list.length;
                var ready = n > 0 && (state.mode !== 'group' || state.group) &&
                    (state.mode !== 'range' || $('rFrom').value || $('rTo').value);

                $('tCount').textContent = ready ? n : 0;
                $('tText').textContent = (ready && n === 1 ? 'ID card' : 'ID cards') +
                    (ready ? ' • ' + Math.ceil(n / 10) + ' A4 sheet' + (Math.ceil(n / 10) === 1 ? '' : 's') : '');
                $('btnPrint').disabled = !ready;

                // group chip counts follow the day / inactive filters
                var d = $('fDays').value,
                    inactive = $('fInactive').checked;
                document.querySelectorAll('[data-count]').forEach(function(el) {
                    var g = el.dataset.count;
                    var c = players.filter(function(p) {
                        return p.group === g && (inactive || p.active) && daysOk(p, d);
                    }).length;
                    el.textContent = c + (c === 1 ? ' player' : ' players');
                });

                if (state.mode === 'range') {
                    var hint = $('rangeHint');
                    if (!$('rFrom').value && !$('rTo').value) {
                        hint.textContent = 'Type the first and last player number. Leave one empty to go from the start or to the end.';
                    } else if (!n) {
                        hint.textContent = 'No players in this range.';
                    } else {
                        hint.textContent = pad(list[0].id) + ' ' + list[0].name + '  →  ' +
                            pad(list[n - 1].id) + ' ' + list[n - 1].name;
                    }
                }
            }

            /* ---------- mode switch ---------- */
            document.querySelectorAll('input[name=mode]').forEach(function(r) {
                r.addEventListener('change', function() {
                    state.mode = r.value;
                    document.querySelectorAll('.pr-panel').forEach(function(p) {
                        p.classList.toggle('show', p.dataset.for.split(' ').indexOf(state.mode) !== -1);
                    });
                    if (state.mode === 'pick') drawList();
                    if (state.mode === 'range') $('rFrom').focus();
                    refresh();
                });
            });

            /* ---------- age group chips ---------- */
            $('groupChips').addEventListener('click', function(e) {
                var b = e.target.closest('.pr-chip');
                if (!b) return;
                state.group = b.dataset.group;
                document.querySelectorAll('.pr-chip').forEach(function(c) {
                    c.classList.toggle('on', c === b);
                });
                refresh();
            });

            ['fDays', 'fInactive', 'rFrom', 'rTo'].forEach(function(id) {
                $(id).addEventListener('input', refresh);
                $(id).addEventListener('change', refresh);
            });

            /* ---------- pick list ---------- */
            function shown() {
                var q = $('pSearch').value.trim().toLowerCase(),
                    g = $('pGroup').value;
                return players.filter(function(p) {
                    if (g && p.group !== g) return false;
                    if (!q) return true;
                    return p.name.toLowerCase().indexOf(q) !== -1 || String(p.no || '').toLowerCase().indexOf(q) !== -1 ||
                        String(p.id) === q.replace(/\D+/g, '').replace(/^0+/, '');
                });
            }

            function drawList() {
                var rows = shown();
                $('pShown').textContent = rows.length + ' shown • ' + state.picked.size + ' selected';
                $('pList').innerHTML = rows.length ? rows.map(function(p) {
                    var on = state.picked.has(p.id);
                    return '<label class="pr-item' + (on ? ' on' : '') + '">' +
                        '<input type="checkbox" value="' + p.id + '"' + (on ? ' checked' : '') + '>' +
                        '<img src="' + AC_esc(p.photo) + '" alt="" loading="lazy">' +
                        '<span class="flex-grow-1"><span class="nm">' + AC_esc(p.name) + '</span>' +
                        (p.active ? '' : '<span class="pr-off">Inactive</span>') +
                        '<span class="sub d-block">' + AC_esc(p.no || '') + ' • ' + AC_esc(p.group || '-') + '</span></span></label>';
                }).join('') : '<div class="text-center text-muted py-4">No players found.</div>';
            }

            function AC_esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : s;
                return d.innerHTML;
            }

            $('pList').addEventListener('change', function(e) {
                if (e.target.type !== 'checkbox') return;
                var id = parseInt(e.target.value, 10);
                e.target.checked ? state.picked.add(id) : state.picked.delete(id);
                e.target.closest('.pr-item').classList.toggle('on', e.target.checked);
                $('pShown').textContent = shown().length + ' shown • ' + state.picked.size + ' selected';
                refresh();
            });

            var t;
            $('pSearch').addEventListener('input', function() {
                clearTimeout(t);
                t = setTimeout(drawList, 150);
            });
            $('pGroup').addEventListener('change', drawList);

            $('pAll').addEventListener('click', function() {
                shown().forEach(function(p) {
                    state.picked.add(p.id);
                });
                drawList();
                refresh();
            });
            $('pNone').addEventListener('click', function() {
                state.picked.clear();
                drawList();
                refresh();
            });

            /* ---------- open the cards ---------- */
            $('btnPrint').addEventListener('click', function() {
                var q = new URLSearchParams();
                if (state.mode === 'pick') {
                    q.set('ids', Array.from(state.picked).join(','));
                } else {
                    if (state.mode === 'group') q.set('group', state.group);
                    if (state.mode === 'range') {
                        if ($('rFrom').value) q.set('from', $('rFrom').value);
                        if ($('rTo').value) q.set('to', $('rTo').value);
                    }
                    if ($('fDays').value) q.set('days', $('fDays').value);
                    if ($('fInactive').checked) q.set('inactive', 1);
                }
                if ($('fSort').value === 'no') q.set('sort', 'no');
                window.open(cardsUrl + '?' + q.toString(), '_blank');
            });

            // ?group=U12 from the Age Groups / Players pages pre-selects that group
            var pre = new URLSearchParams(location.search).get('group');
            if (pre) {
                $('m-group').checked = true;
                $('m-group').dispatchEvent(new Event('change'));
                var chip = document.querySelector('.pr-chip[data-group="' + CSS.escape(pre) + '"]');
                if (chip) chip.click();
            }

            refresh();
        })();
    </script>
@endsection
