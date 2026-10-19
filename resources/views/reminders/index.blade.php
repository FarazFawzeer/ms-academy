@extends('layouts.vertical', ['subtitle' => 'Reminders'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Reminders', 'subtitle' => 'WhatsApp'])
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

        .act-wa {
            color: #16a34a;
        }

        .act-paid {
            color: #2563eb;
        }

        .act-dismiss {
            color: #dc2626;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Renewal Reminders to Send</h5>
            <p class="card-subtitle mb-0">
                Added automatically when a player completes session {{ config('academy.remind_at_session') }} of
                {{ config('academy.sessions_per_package') }}. Tap <strong>WhatsApp</strong>, the message is already typed -
                just press send.
            </p>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player</th>
                            <th class="d-none d-md-table-cell">Parent</th>
                            <th>Sessions</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="pendingBody">
                        @foreach ($pending as $p)
                            <tr id="rem-{{ $p->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $p->photo_url }}" class="avatar-sm rounded-circle"
                                            style="object-fit:cover" alt="">
                                        <div>
                                            <h6 class="mb-0">{{ $p->name }}</h6>
                                            <small class="text-muted">{{ $p->player_no }} ·
                                                {{ $p->age_group }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    {{ $p->guardian_name }}<br><small class="text-muted">{{ $p->guardian_phone }}</small>
                                </td>
                                <td data-balance="{{ $p->id }}">{{ $p->balanceBadge() }}</td>
                                <td class="text-end text-nowrap">
                                    @if ($p->whatsappLink())
                                        <a href="{{ $p->whatsappLink() }}" target="_blank" rel="noopener"
                                            class="act-ico act-wa wa-send" data-id="{{ $p->id }}"
                                            title="Send WhatsApp reminder">
                                            <iconify-icon icon="logos:whatsapp-icon"></iconify-icon>
                                        </a>
                                    @else
                                        <span class="text-danger small">No valid number</span>
                                    @endif
                                    <button type="button" class="act-ico act-paid"
                                        onclick="openPayment({{ $p->id }}, @js($p->name))"
                                        title="Parent already paid">
                                        <iconify-icon icon="solar:wallet-bold-duotone"></iconify-icon>
                                    </button>
                                    <button type="button" class="act-ico act-dismiss dismiss"
                                        data-id="{{ $p->id }}" title="Remove without sending">
                                        <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="emptyRow" class="{{ $pending->isEmpty() ? '' : 'd-none' }}">
                            <td colspan="4" class="text-center text-muted py-4">No reminders waiting. 🎉</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($recent->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recently Sent</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($recent as $r)
                                <tr>
                                    <td><a href="{{ route('admin.players.show', $r) }}">{{ $r->name }}</a>
                                        <small class="text-muted">· {{ $r->guardian_name }}</small>
                                    </td>
                                    <td class="text-end text-muted">{{ $r->last_reminded_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @include('players._payment-modal')

    <script>
        (function() {
            var sentTpl = "{{ route('admin.reminders.sent', ['player' => '__ID__']) }}";
            var dismissTpl = "{{ route('admin.reminders.dismiss', ['player' => '__ID__']) }}";
            var body = document.getElementById('pendingBody');

            function removeRow(id) {
                var row = document.getElementById('rem-' + id);
                if (row) row.remove();
                if (!body.querySelector('tr[id^="rem-"]')) document.getElementById('emptyRow').classList.remove('d-none');
            }

            body.addEventListener('click', async function(e) {
                // opening WhatsApp is the link's normal behaviour - we only record that it was sent
                var wa = e.target.closest('.wa-send');
                if (wa) {
                    var id = wa.dataset.id;
                    var d = await AC.call(sentTpl.replace('__ID__', id));
                    if (d.success) {
                        AC.toast('Marked as sent');
                        removeRow(id);
                    }
                    return;
                }

                var dis = e.target.closest('.dismiss');
                if (dis) {
                    AC.act(dismissTpl.replace('__ID__', dis.dataset.id), {
                        confirm: {
                            title: 'Remove this reminder?',
                            text: 'It will not be sent.',
                            button: 'Yes, remove'
                        },
                        onSuccess: function() {
                            removeRow(dis.dataset.id);
                        }
                    });
                }
            });

            // parent already paid -> payment clears the reminder on the server, so drop the row
            document.addEventListener('payment:saved', function(ev) {
                removeRow(ev.detail.id);
            });
        })();
    </script>
@endsection