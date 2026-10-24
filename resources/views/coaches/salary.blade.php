@extends('layouts.vertical', ['subtitle' => 'Coach Salary'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => 'Salary'])
    @include('partials.js-helpers')

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Salary - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h5>
                    <p class="card-subtitle mb-0">Days worked x daily rate. Press "Paid" after you hand over the salary.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.coaches.index') }}" class="btn btn-light ac-btn">
                    <iconify-icon icon="solar:arrow-left-outline"></iconify-icon> Coaches
                </a>
                    <a href="{{ route('admin.coaches.workdays') }}" class="btn btn-outline-primary ac-btn">
                        <iconify-icon icon="solar:calendar-mark-outline"></iconify-icon> Daily Attendance
                    </a>
                    <a href="{{ route('admin.reports.export', ['type' => 'salary', 'from' => $month . '-01', 'to' => \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString()]) }}"
                        class="btn btn-outline-secondary">Download Excel</a>
                </div>
            </div>

            <form method="GET" class="row g-2">
                <div class="col-8 col-md-3">
                    <input type="month" name="month" id="monthInput" value="{{ $month }}" class="form-control">
                </div>
                <div class="col-4 col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Show</button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row row-cols-3 g-3 mb-3">
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Days worked</p>
                        <h4 class="mb-0">{{ number_format($sum['days']) }}</h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Total (LKR)</p>
                        <h4 class="mb-0">{{ number_format($sum['total']) }}</h4>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 text-center">
                        <p class="text-muted mb-1">Still to pay (LKR)</p>
                        <h4 class="mb-0 {{ $sum['unpaid'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($sum['unpaid']) }}</h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Coach</th>
                            <th class="d-none d-sm-table-cell">Days</th>
                            <th class="d-none d-md-table-cell">Rate</th>
                            <th class="d-none d-sm-table-cell">Total</th>
                            <th class="d-none d-sm-table-cell">To pay</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                @php
                                    $toPay = $r->unpaid > 0
                                        ? \App\Models\Player::softBadge('#b91c1c', 'LKR ' . number_format($r->unpaid), 96)
                                        : \App\Models\Player::softBadge($r->days ? '#15803d' : '#6b7280', $r->days ? 'Paid' : '-', 96);
                                @endphp
                                <td>
                                    <span class="ac-person">
                                        <img src="{{ $r->photo_path ? asset($r->photo_path) : asset('images/users/avatar-6.jpg') }}" alt="">
                                        <span>
                                            <span class="nm d-block">{{ $r->name }}</span>
                                            {{-- phones: days, total and amount due under the name --}}
                                            <span class="sub d-block d-sm-none">{{ $r->days }} day(s) &middot; LKR {{ number_format($r->total) }}</span>
                                            <span class="d-block d-sm-none mt-1">{{ $toPay }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="d-none d-sm-table-cell">{{ $r->days }}</td>
                                <td class="d-none d-md-table-cell">{{ number_format($r->daily_rate) }}</td>
                                <td class="d-none d-sm-table-cell">{{ number_format($r->total) }}</td>
                                <td class="d-none d-sm-table-cell">{{ $toPay }}</td>
                                <td>
                                    <div class="ac-actions">
                                        <button type="button" class="btn btn-sm btn-soft-secondary btn-days" title="View the days worked"
                                            data-id="{{ $r->id }}" data-name="{{ $r->name }}" {{ $r->days ? '' : 'disabled' }}>
                                            <iconify-icon icon="solar:calendar-outline"></iconify-icon><span class="ac-hide-xs">Days</span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success btn-pay" data-id="{{ $r->id }}"
                                            data-name="{{ $r->name }}" data-amount="{{ number_format($r->unpaid) }}"
                                            {{ $r->unpaid > 0 ? '' : 'disabled' }}>
                                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon><span>Paid</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No coaches yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var month = "{{ $month }}";
            var daysTpl = "{{ route('admin.coaches.days', ['coach' => '__ID__']) }}";
            var payUrl = "{{ route('admin.coaches.salary.pay') }}";

            document.getElementById('monthInput').addEventListener('change', function() {
                this.form.submit();
            });

            document.querySelectorAll('.btn-days').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var rows = await AC.call(daysTpl.replace('__ID__', b.dataset.id) + '?month=' + month, {
                        method: 'GET'
                    });
                    if (!Array.isArray(rows)) {
                        return Swal.fire('Error', rows.message || 'Could not load.', 'error');
                    }
                    var html = '<div class="text-start">' + rows.map(function(r) {
                        return '<div class="d-flex justify-content-between border-bottom py-1"><span>' + AC.esc(r.date) +
                            '</span><span>LKR ' + AC.esc(r.amount) + ' ' +
                            (r.paid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>') +
                            '</span></div>';
                    }).join('') + '</div>';
                    Swal.fire({
                        title: b.dataset.name,
                        html: html,
                        confirmButtonText: 'Close'
                    });
                });
            });

            document.querySelectorAll('.btn-pay').forEach(function(b) {
                b.addEventListener('click', async function() {
                    var ok = await AC.ask('Mark as paid?', b.dataset.name + ': LKR ' + b.dataset.amount +
                        ' for this month will be marked as paid. Paid days can no longer be changed.', 'Yes, mark paid');
                    if (!ok) return;
                    var d = await AC.call(payUrl, {
                        body: {
                            coach_id: b.dataset.id,
                            month: month
                        }
                    });
                    if (d.success) {
                        AC.toast(d.message);
                        setTimeout(function() {
                            location.reload();
                        }, 700);
                    } else {
                        Swal.fire('Error', AC.errors(d).replace(/<br>/g, ' '), 'error');
                    }
                });
            });
        })();
    </script>
@endsection
