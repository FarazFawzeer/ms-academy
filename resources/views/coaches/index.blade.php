@extends('layouts.vertical', ['subtitle' => 'Coaches'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'All Coaches'])
    @include('partials.js-helpers')

    <style>
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

        .act-off {
            color: #dc2626;
        }

        .act-on {
            color: #16a34a;
        }

        .st-badge {
            display: inline-block;
            width: 80px;
            text-align: center;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            padding: 6px 0;
            border-radius: 6px;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="card-title mb-0">Coach List</h5>
                <p class="card-subtitle mb-0">Coaches are paid a fixed amount for each day they work.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.coaches.workdays') }}" class="btn btn-outline-primary">Daily Attendance</a>
                <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">
                    <iconify-icon icon="solar:user-plus-outline" class="me-1"></iconify-icon> Add Coach
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Coach</th>
                            <th class="d-none d-md-table-cell">Phone</th>
                            <th>Daily Rate (LKR)</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coaches as $c)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $c->photo_url }}" class="avatar-sm rounded-circle"
                                            style="object-fit:cover" alt="">
                                        <div>
                                            <h6 class="mb-0">{{ $c->name }}</h6>
                                            @if ($c->nic)
                                                <small class="text-muted">NIC {{ $c->nic }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $c->phone ?? '-' }}</td>
                                <td>{{ number_format($c->daily_rate) }}</td>
                                <td>
                                    <span class="st-badge"
                                        style="background:{{ $c->is_active ? '#16a34a' : '#6b7280' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.coaches.edit', $c) }}" class="act-ico act-edit"
                                        title="Edit coach">
                                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                    </a>
                                    <button type="button"
                                        class="act-ico btn-toggle {{ $c->is_active ? 'act-off' : 'act-on' }}"
                                        data-id="{{ $c->id }}" data-active="{{ $c->is_active ? 1 : 0 }}"
                                        title="{{ $c->is_active ? 'Deactivate coach' : 'Activate coach' }}">
                                        <iconify-icon
                                            icon="{{ $c->is_active ? 'solar:user-block-bold-duotone' : 'solar:user-check-bold-duotone' }}"></iconify-icon>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No coaches yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        var toggleTpl = "{{ route('admin.coaches.toggle', ['coach' => '__ID__']) }}";
        document.querySelectorAll('.btn-toggle').forEach(function(b) {
            b.addEventListener('click', function() {
                AC.act(toggleTpl.replace('__ID__', b.dataset.id), {
                    confirm: b.dataset.active === '1' ? {
                        title: 'Deactivate this coach?',
                        text: 'They will no longer appear in daily attendance. Salary history is kept.',
                        button: 'Yes, deactivate'
                    } : null,
                    onSuccess: function() {
                        setTimeout(function() {
                            location.reload();
                        }, 600);
                    }
                });
            });
        });
    </script>
@endsection