@extends('layouts.vertical', ['subtitle' => 'Coaches'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'All Coaches'])
    @include('partials.js-helpers')

    <style>





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
                <a href="{{ route('admin.coaches.workdays') }}" class="btn btn-outline-primary ac-btn">
                    <iconify-icon icon="solar:calendar-mark-outline"></iconify-icon> Daily Attendance
                </a>
                <a href="{{ route('admin.coaches.salary') }}" class="btn btn-outline-primary ac-btn">
                    <iconify-icon icon="solar:wallet-money-outline"></iconify-icon> Salary
                </a>
                <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary ac-btn">
                    <iconify-icon icon="solar:user-plus-outline"></iconify-icon> Add Coach
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
                            <th class="d-none d-sm-table-cell">Daily Rate (LKR)</th>
                            <th class="d-none d-sm-table-cell">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coaches as $c)
                            <tr>
                                <td>
                                    <span class="ac-person">
                                        <img src="{{ $c->photo_url }}" alt="">
                                        <span>
                                            <span class="nm d-block">{{ $c->name }}</span>
                                            @if ($c->nic)
                                                <span class="sub d-none d-sm-block">NIC {{ $c->nic }}</span>
                                            @endif
                                            {{-- phones: rate and status under the name --}}
                                            <span class="sub d-block d-sm-none">LKR {{ number_format($c->daily_rate) }} / day</span>
                                            <span class="d-block d-sm-none mt-1">{{ \App\Models\Player::softBadge($c->is_active ? '#15803d' : '#4b5563', $c->is_active ? 'Active' : 'Inactive', 80) }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $c->phone ?? '-' }}</td>
                                <td class="d-none d-sm-table-cell">{{ number_format($c->daily_rate) }}</td>
                                <td class="d-none d-sm-table-cell">
                                    {{ \App\Models\Player::softBadge($c->is_active ? '#15803d' : '#4b5563', $c->is_active ? 'Active' : 'Inactive', 80) }}
                                </td>
                                <td>
                                    <div class="ac-actions">
                                        <a href="{{ route('admin.coaches.edit', $c) }}" class="btn btn-sm btn-soft-primary">
                                            <iconify-icon icon="solar:pen-outline"></iconify-icon><span>Edit</span>
                                        </a>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-soft-secondary ac-more"
                                                data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions" title="More actions">
                                                <iconify-icon icon="solar:menu-dots-bold"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.coaches.workdays') }}">
                                                        <iconify-icon icon="solar:calendar-mark-outline"></iconify-icon> Daily attendance</a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.coaches.salary') }}">
                                                        <iconify-icon icon="solar:wallet-money-outline"></iconify-icon> Salary</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><button type="button" class="dropdown-item btn-toggle {{ $c->is_active ? 'text-danger' : 'text-success' }}"
                                                        data-id="{{ $c->id }}" data-active="{{ $c->is_active ? 1 : 0 }}">
                                                        <iconify-icon icon="{{ $c->is_active ? 'solar:user-block-outline' : 'solar:user-check-outline' }}"></iconify-icon>
                                                        {{ $c->is_active ? 'Deactivate coach' : 'Activate coach' }}</button></li>
                                            </ul>
                                        </div>
                                    </div>
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