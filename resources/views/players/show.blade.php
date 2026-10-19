@extends('layouts.vertical', ['subtitle' => $player->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'Profile'])
    @include('partials.js-helpers')

    @php $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']); @endphp

    <div class="row g-3">
        {{-- Profile --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $player->photo_url }}" alt="{{ $player->name }}" class="rounded-circle mb-3"
                        style="width:120px;height:120px;object-fit:cover">
                    <h4 class="mb-1">{{ $player->name }}</h4>
                    <p class="text-muted mb-2">{{ $player->player_no }}</p>
                    <div class="d-flex justify-content-center gap-2 mb-2">
                        {{ $player->ageGroupBadge() }} {{ $player->daysBadge() }}
                    </div>
                    <span class="badge bg-{{ $player->is_active ? 'success' : 'secondary' }}"
                        id="statusBadge">{{ $player->is_active ? 'Active' : 'Inactive' }}</span>

                    <hr>

                    <div class="text-start">
                        <p class="mb-2"><span class="text-muted">Date of birth</span><br>
                            {{ $player->dob->format('d M Y') }} ({{ $player->age }} yrs)
                            @if ($nextMove)
                                <br><small class="text-muted">Moves to the next age group on
                                    {{ $nextMove->format('d M Y') }}</small>
                            @endif
                        </p>
                        <p class="mb-2"><span class="text-muted">Parent / Guardian</span><br>
                            {{ $player->guardian_name }}</p>
                        <p class="mb-2"><span class="text-muted">Parent number</span><br>
                            <a href="tel:{{ $player->guardian_phone }}">{{ $player->guardian_phone }}</a>
                            @if ($player->whatsapp_number)
                                · <a href="https://wa.me/{{ $player->whatsapp_number }}" target="_blank"
                                    rel="noopener">WhatsApp</a>
                            @endif
                        </p>
                        @if ($player->phone)
                            <p class="mb-2"><span class="text-muted">Player phone</span><br>{{ $player->phone }}</p>
                        @endif
                        <p class="mb-0"><span class="text-muted">Joined</span><br>
                            {{ $player->joined_at?->format('d M Y') ?? '-' }}</p>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.players.cards', ['ids' => $player->id]) }}" target="_blank"
                            class="btn btn-dark">
                            <iconify-icon icon="solar:printer-outline" class="me-1"></iconify-icon> Print ID Card
                        </a>
                        <a href="{{ route('admin.players.edit', $player) }}" class="btn btn-outline-primary">Edit Profile</a>
                        <button type="button" class="btn btn-outline-{{ $player->is_active ? 'danger' : 'success' }}"
                            id="toggleBtn">{{ $player->is_active ? 'Deactivate Player' : 'Activate Player' }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sessions, payments, attendance --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="text-muted mb-1">Paid sessions left</p>
                            <h2 class="mb-0" data-balance="{{ $player->id }}">{{ $player->balanceBadge() }}</h2>
                        </div>
                        <div>
                            <p class="text-muted mb-1">Total sessions attended</p>
                            <h2 class="mb-0">{{ $totalAttended }}</h2>
                        </div>
                        <button type="button" class="btn btn-success btn-lg"
                            onclick="openPayment({{ $player->id }}, @js($player->name))">
                            <iconify-icon icon="solar:wallet-outline" class="me-1"></iconify-icon> Receive Payment
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payments</h5>
                    <p class="card-subtitle mb-0">Last 10 payments.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="payTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount (LKR)</th>
                                    <th>Sessions</th>
                                    <th class="d-none d-sm-table-cell">Method</th>
                                    <th class="d-none d-md-table-cell">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $pay)
                                    <tr>
                                        <td>{{ $pay->paid_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ number_format($pay->amount) }}</td>
                                        <td>+{{ $pay->sessions }}</td>
                                        <td class="d-none d-sm-table-cell">{{ ucfirst($pay->method) }}</td>
                                        <td class="d-none d-md-table-cell">{{ $pay->note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No payments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance</h5>
                    <p class="card-subtitle mb-0">Last 30 sessions.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th>How</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendance as $a)
                                    <tr>
                                        <td>{{ $a->attendance_date->format('D, d M Y') }}</td>
                                        <td>{{ $a->created_at?->format('h:i A') }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $a->age_group ?? '-' }}</td>
                                        <td>{{ $a->method === 'qr' ? 'QR scan' : 'Manual' }}
                                            @if ($a->is_extra)
                                                <span class="badge ms-1" style="background:#2563eb1a;color:#2563eb">Extra day</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No attendance yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('players._payment-modal')

    <script>
        // new payment -> reload so the payment table and balance are both fresh
        document.addEventListener('payment:saved', function() {
            setTimeout(function() {
                location.reload();
            }, 900);
        });

        document.getElementById('toggleBtn').addEventListener('click', function() {
            var active = {{ $player->is_active ? 'true' : 'false' }};
            AC.act("{{ route('admin.players.toggle', $player) }}", {
                confirm: active ? {
                    title: 'Deactivate this player?',
                    text: 'They will be hidden from the scanner. Their history is kept.',
                    button: 'Yes, deactivate'
                } : null,
                onSuccess: function() {
                    setTimeout(function() {
                        location.reload();
                    }, 700);
                }
            });
        });
    </script>
@endsection
