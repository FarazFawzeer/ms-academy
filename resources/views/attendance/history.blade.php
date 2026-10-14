@extends('layouts.vertical', ['subtitle' => 'Attendance History'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'History'])

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</h5>
                    <p class="card-subtitle mb-0">{{ $rows->count() }} player(s) attended.</p>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-6 col-md-3">
                    <input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}"
                        class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <select name="batch_id" class="form-select">
                        <option value="">All batches</option>
                        @foreach ($batches as $b)
                            <option value="{{ $b->id }}" {{ $batchId == $b->id ? 'selected' : '' }}>{{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player</th>
                            <th class="d-none d-sm-table-cell">Batch</th>
                            <th>Time</th>
                            <th class="d-none d-sm-table-cell">How</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.players.show', $r->player_id) }}"
                                        class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                                        <img src="{{ $r->photo_path ? asset($r->photo_path) : asset('images/users/avatar-6.jpg') }}"
                                            class="avatar-sm rounded-circle" style="object-fit:cover" alt=""
                                            loading="lazy">
                                        <div>
                                            <h6 class="mb-0">{{ $r->name }}</h6>
                                            <small class="text-muted">{{ $r->player_no }}</small>
                                        </div>
                                    </a>
                                </td>
                                <td class="d-none d-sm-table-cell">{{ $r->batch_name ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->created_at)->format('h:i A') }}</td>
                                <td class="d-none d-sm-table-cell">{{ $r->method === 'qr' ? 'QR scan' : 'Manual' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No attendance on this day.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
