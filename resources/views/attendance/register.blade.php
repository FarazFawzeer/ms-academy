@extends('layouts.vertical', ['subtitle' => 'Attendance Register'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'Register'])

    @php
        $prev = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->subMonth()->format('Y-m');
        $next = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->addMonth()->format('Y-m');
        $keep = array_filter(['group' => $group, 'days' => $days]);
        $span = count($columns) + 3;
        $rateColour = fn($r) => $r === null ? '#6b7280' : ($r >= 80 ? '#15803d' : ($r >= 50 ? '#b45309' : '#b91c1c'));
        $lastGroup = null;
    @endphp

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

            .rg-filters .rg-sel {
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

        .rg-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Grid */
        .rg-wrap {
            overflow-x: auto;
            border: 1px solid rgba(100, 116, 139, .2);
            border-radius: 10px;
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
        .ex {
            font-size: 22px;
            line-height: 1;
        }

        .ok {
            color: #16a34a;
        }

        .ex {
            color: #2563eb;
        }

        .ab {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 5px;
            border-radius: 4px;
            background: #dc26261a;
            color: #b91c1c;
        }

        .ab-free {
            background: #6b72801a;
            color: #6b7280;
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

        .rg tbody tr:not(.grp):hover td {
            background: rgba(15, 118, 110, .04);
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
            padding: 0 8px;
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
                    <h5 class="card-title mb-0">Attendance Register - {{ $monthTx }}</h5>
                    <p class="card-subtitle mb-0">{{ count($rows) }} active player(s) &middot; {{ count($columns) }} day(s)
                        this month.</p>
                </div>
                <a href="{{ route('admin.attendance.register.export', array_filter(['month' => $month] + $keep)) }}"
                    class="btn btn-outline-success">
                    <iconify-icon icon="solar:file-download-outline" class="me-1"></iconify-icon> Excel
                </a>
            </div>

            {{-- Auto filter: no Search button --}}
            <form method="GET" id="rgFilter" class="rg-filters">
                <div class="rg-month">
                    <a href="{{ route('admin.attendance.register', ['month' => $prev] + $keep) }}" title="Previous month">
                        <iconify-icon icon="solar:alt-arrow-left-outline"></iconify-icon></a>
                    <input type="month" name="month" value="{{ $month }}" class="form-control"
                        onchange="this.form.submit()">
                    <a href="{{ route('admin.attendance.register', ['month' => $next] + $keep) }}" title="Next month">
                        <iconify-icon icon="solar:alt-arrow-right-outline"></iconify-icon></a>
                </div>

                <select name="group" class="form-select rg-sel" onchange="this.form.submit()">
                    <option value="">All age groups</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g }}" {{ $group === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>

                <select name="days" class="form-select rg-sel" onchange="this.form.submit()">
                    <option value="">All training days</option>
                    @foreach ($slots as $key => $s)
                        <option value="{{ $key }}" {{ $days === $key ? 'selected' : '' }}>Trains on {{ $s['label'] }}
                        </option>
                    @endforeach
                    <option value="both" {{ $days === 'both' ? 'selected' : '' }}>Both days</option>
                </select>
            </form>
        </div>

        <div class="card-body">
            <div class="rg-legend mb-3">
                <span><iconify-icon icon="solar:check-circle-bold" class="ok" style="font-size:16px"></iconify-icon>
                    Present</span>
                <span><span class="ab">AB</span> Absent (session used)</span>
                <span><span class="ab ab-free">AB</span> Absent, not counted</span>
                <span><iconify-icon icon="solar:check-circle-bold" class="ex" style="font-size:16px"></iconify-icon>
                    Extra (not their day)</span>
                <span><span class="na">&middot;</span> Not their training day</span>
                <span><span
                        style="display:inline-block;width:16px;height:12px;background:repeating-linear-gradient(45deg,#f1f5f9,#f1f5f9 3px,#e2e8f0 3px,#e2e8f0 6px)"></span>
                    No practice</span>
            </div>

            @if (empty($columns))
                <p class="text-center text-muted py-5 mb-0">No practice days in {{ $monthTx }}.</p>
            @else
                <div class="rg-wrap">
                    <table class="rg">
                        <thead>
                            <tr>
                                <th class="rg-name">Player</th>
                                @foreach ($columns as $c)
                                    @php
                                        $cls = $c['type'] === 'cancelled' ? 'cancel' : ($c['type'] === 'extra' ? 'extra' : '');
                                        $tip = $c['type'] === 'cancelled'
                                            ? 'No practice' . ($c['session']->note ? ': ' . $c['session']->note : '')
                                            : ($c['type'] === 'extra'
                                                ? 'Extra day (marked by an Admin)'
                                                : $c['session']->label . ' ' . $c['session']->time_text . ($c['moved'] ? ' - moved from ' . $c['session']->planned_date->format('D d M') : ''));
                                    @endphp
                                    <th class="rg-col {{ $cls }}" title="{{ $tip }}">
                                        <span class="d">{{ $c['date']->format('D') }}</span>
                                        <span class="n">{{ $c['date']->format('d') }}</span>
                                        @if ($c['type'] === 'cancelled')
                                            <span class="tag">OFF</span>
                                        @elseif ($c['type'] === 'extra')
                                            <span class="tag">EXTRA</span>
                                        @elseif ($c['moved'])
                                            <span class="tag">MOVED</span>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="sum">Attended</th>
                                <th class="sum">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                @if ($r['group'] !== $lastGroup)
                                    @php $lastGroup = $r['group']; @endphp
                                    <tr class="grp">
                                        <td class="rg-name">
                                            {{ \App\Models\Player::softBadge(\App\Support\AgeGroup::colour($r['group']), $r['group'] ?? '-', 64) }}
                                            @php $n = collect($rows)->where('group', $r['group'])->count(); @endphp
                                            <span class="ms-1">{{ $n }} {{ \Illuminate\Support\Str::plural('player', $n) }}</span>
                                        </td>
                                        <td colspan="{{ $span - 1 }}"></td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="rg-name">
                                        <a href="{{ route('admin.players.show', $r['player']->id) }}">{{ $r['player']->name }}</a>
                                        <small>{{ $r['player']->player_no }} &middot; {{ $r['player']->days_text }}</small>
                                    </td>
                                    @foreach ($r['cells'] as $cell)
                                        @switch($cell)
                                            @case('P')
                                                <td class="c"><iconify-icon icon="solar:check-circle-bold" class="ok"></iconify-icon></td>
                                            @break

                                            @case('E')
                                                <td class="c" title="Extra (not their training day)"><iconify-icon
                                                        icon="solar:check-circle-bold" class="ex"></iconify-icon></td>
                                            @break

                                            @case('A')
                                                <td class="c"><span class="ab">AB</span></td>
                                            @break

                                            @case('U')
                                                <td class="c" title="Absent - not counted, the package was already used up"><span class="ab ab-free">AB</span></td>
                                            @break

                                            @case('T')
                                                <td class="c" title="Today - not marked yet"><span class="na">&ndash;</span></td>
                                            @break

                                            @case('X')
                                                <td class="c x"></td>
                                            @break

                                            @case('N')
                                                <td class="c"><span class="na">&middot;</span></td>
                                            @break

                                            @default
                                                <td class="c"></td>
                                        @endswitch
                                    @endforeach
                                    <td class="sum">{{ $r['attended'] }} <span class="text-muted">/ {{ $r['expected'] }}</span></td>
                                    <td class="sum">
                                        <span class="rate"
                                            style="background:{{ $rateColour($r['rate']) }}1a;color:{{ $rateColour($r['rate']) }}">
                                            {{ $r['rate'] === null ? '-' : $r['rate'] . '%' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $span }}" class="text-center text-muted py-4">No players match these
                                        filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($rows))
                            <tfoot>
                                <tr>
                                    <td class="rg-name">Present</td>
                                    @foreach ($present as $i => $n)
                                        <td>{{ $columns[$i]['type'] === 'cancelled' ? '' : $n }}</td>
                                    @endforeach
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">"Attended / expected" counts this month. Rate = attended on their own
                    training days &divide; expected (practices held so far).</p>
            @endif
        </div>
    </div>
@endsection
