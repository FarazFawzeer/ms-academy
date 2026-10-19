@extends('layouts.vertical', ['subtitle' => 'Reports'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Reports', 'subtitle' => 'Summary'])

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} -
                        {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</h5>
                    <p class="card-subtitle mb-0">Pick any period. Excel files use the same dates.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach (['attendance' => 'Attendance', 'payments' => 'Payments', 'salary' => 'Coach Salary'] as $key => $label)
                        <a href="{{ route('admin.reports.export', ['type' => $key, 'from' => $from, 'to' => $to]) }}"
                            class="btn btn-outline-secondary btn-sm">
                            <iconify-icon icon="solar:download-minimalistic-outline" class="me-1"></iconify-icon>
                            {{ $label }} Excel
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-6 col-md-3">
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-light">This month</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row row-cols-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Sessions attended</p>
                        <h4 class="mb-0">{{ number_format($sum->sessions) }}</h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Fees received (LKR)</p>
                        <h4 class="mb-0 text-success">{{ number_format($sum->revenue) }}</h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Coach salary (LKR)</p>
                        <h4 class="mb-0 text-danger">{{ number_format($sum->salary) }}</h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">New players</p>
                        <h4 class="mb-0">{{ number_format($sum->new_players) }}</h4>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <span class="text-muted">Fees minus coach salary:</span>
                @php $net = $sum->revenue - $sum->salary; @endphp
                <strong class="{{ $net >= 0 ? 'text-success' : 'text-danger' }}">LKR {{ number_format($net) }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">By Age Group</h5>
                    <p class="card-subtitle mb-0">Group on the day the player attended.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Group</th>
                                    <th>Sessions</th>
                                    <th>Players</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($byGroup as $b)
                                    <tr>
                                        <td>{{ \App\Models\Player::softBadge(\App\Support\AgeGroup::colour($b->grp), $b->grp ?? '-', 64) }}</td>
                                        <td>{{ number_format($b->sessions) }}</td>
                                        <td>{{ number_format($b->players) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No attendance in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">By Player</h5>
                    <p class="card-subtitle mb-0">Active players, most sessions first. Players with 0 may be absent for a long time.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th>Sessions</th>
                                    <th class="d-none d-md-table-cell">Last seen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($byPlayer as $p)
                                    <tr>
                                        <td><a href="{{ route('admin.players.show', $p->id) }}">{{ $p->name }}</a><br>
                                            <small class="text-muted">{{ $p->player_no }}</small></td>
                                        <td class="d-none d-sm-table-cell">{{ $p->grp ?? '-' }}</td>
                                        <td>{{ $p->sessions }}</td>
                                        <td class="d-none d-md-table-cell">
                                            {{ $p->last_seen ? \Carbon\Carbon::parse($p->last_seen)->format('d M') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
