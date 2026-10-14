@extends('layouts.vertical', ['subtitle' => 'Scan Attendance'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'Scan'])
    @include('partials.js-helpers')

    <style>
        #reader video {
            width: 100% !important;
            border-radius: .5rem;
        }

        #reader {
            border: 0 !important;
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

        .act-undo {
            color: #dc2626;
        }

        .act-present {
            color: #16a34a;
            font-size: 28px;
            width: 40px;
            height: 40px;
        }
    </style>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mark Attendance</h5>
                    <span class="badge bg-primary fs-12" id="todayBadge">{{ $today->count() }} today</span>
                </div>

                <div class="card-body">
                    <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#tabScan"
                                role="tab">Scan QR</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tabSearch" role="tab">Search
                                name</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabScan" role="tabpanel">
                            <div id="reader" class="bg-dark rounded" style="min-height:240px"></div>
                            <div class="d-grid mt-3">
                                <button type="button" id="btnCamera" class="btn btn-primary btn-lg">Start Camera</button>
                            </div>
                            <p class="text-muted small mt-2 mb-0" id="camHint">Hold the player's card in front of the camera.
                                The camera needs a secure (https) connection.</p>
                        </div>

                        <div class="tab-pane fade" id="tabSearch" role="tabpanel">
                            <input type="search" id="searchBox" class="form-control form-control-lg"
                                placeholder="Type name, ID (MS-0012) or phone" autocomplete="off">
                            <div id="searchResults" class="list-group mt-2"></div>
                        </div>
                    </div>

                    <div id="result" class="mt-3"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Today - {{ today()->format('l, d M Y') }}</h5>
                    <p class="card-subtitle mb-0">Newest first. Use Undo if someone was marked by mistake.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Batch</th>
                                    <th>Time</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="todayBody">
                                @foreach ($today as $a)
                                    <tr id="att-{{ $a->id }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $a->photo_path ? asset($a->photo_path) : asset('images/users/avatar-6.jpg') }}"
                                                    class="avatar-sm rounded-circle" style="object-fit:cover" alt="">
                                                <div>
                                                    <h6 class="mb-0">{{ $a->name }}</h6>
                                                    <small class="text-muted">{{ $a->player_no }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-sm-table-cell">{{ $a->batch_name ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($a->created_at)->format('h:i A') }}</td>
                                        <td class="text-end">
                                            <button type="button" class="act-ico act-undo undo" data-id="{{ $a->id }}"
                                                title="Undo (marked by mistake)">
                                                <iconify-icon icon="solar:undo-left-round-bold-duotone"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr id="emptyRow" class="{{ $today->isEmpty() ? '' : 'd-none' }}">
                                    <td colspan="4" class="text-center text-muted py-4">No one has been marked yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('players._payment-modal')

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function() {
            var markUrl = "{{ route('admin.attendance.mark') }}";
            var searchUrl = "{{ route('admin.attendance.search') }}";
            var undoTpl = "{{ route('admin.attendance.destroy', ['attendance' => '__ID__']) }}";

            var resultBox = document.getElementById('result');
            var todayBody = document.getElementById('todayBody');
            var emptyRow = document.getElementById('emptyRow');
            var badge = document.getElementById('todayBadge');
            var count = {{ $today->count() }};

            function setCount(n) {
                count = Math.max(0, n);
                badge.textContent = count + ' today';
                emptyRow.classList.toggle('d-none', count > 0);
            }

            /* ---------- feedback: beep + vibrate ---------- */
            var audioCtx;

            function beep(ok) {
                try {
                    audioCtx = audioCtx || new(window.AudioContext || window.webkitAudioContext)();
                    var o = audioCtx.createOscillator(),
                        g = audioCtx.createGain();
                    o.connect(g);
                    g.connect(audioCtx.destination);
                    o.frequency.value = ok ? 880 : 300;
                    g.gain.value = 0.08;
                    o.start();
                    o.stop(audioCtx.currentTime + (ok ? 0.12 : 0.3));
                } catch (e) {}
                if (navigator.vibrate) navigator.vibrate(ok ? 80 : [120, 60, 120]);
            }

            /* ---------- result card ---------- */
            function showResult(d) {
                var p = d.player || {};
                var cls = 'success',
                    title = '',
                    sub = '',
                    extra = '';

                if (d.status === 'marked') {
                    if (d.payment_due) {
                        cls = 'danger';
                        title = 'Marked - PAYMENT DUE';
                        sub = 'Please collect the fee (LKR {{ number_format(config('academy.fee')) }}).';
                    } else {
                        title = 'Attendance marked';
                        sub = 'Session ' + d.session + ' of ' + d.total + ' &middot; ' + d.balance + ' left';
                    }
                    if (d.reminder) extra = '<div class="mt-1"><span class="badge bg-warning text-dark">Renewal reminder queued</span></div>';
                } else if (d.status === 'duplicate') {
                    cls = 'warning';
                    title = 'Already marked today';
                } else if (d.status === 'inactive') {
                    cls = 'warning';
                    title = 'Player is inactive';
                } else {
                    cls = 'danger';
                    title = AC.esc(d.message || 'Not recognised');
                }

                var payBtn = (d.status === 'marked' && (d.payment_due || d.reminder)) ?
                    '<button type="button" class="btn btn-sm btn-success mt-2" onclick="openPayment(' + p.id + ', ' +
                    JSON.stringify(p.name || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;') +
                    ')">Receive payment</button>' : '';

                var who = p.name ?
                    '<img src="' + AC.esc(p.photo) + '" class="rounded-circle flex-shrink-0" style="width:64px;height:64px;object-fit:cover" alt="">' :
                    '';

                resultBox.innerHTML =
                    '<div class="alert alert-' + cls + ' d-flex align-items-center gap-3 mb-0">' + who +
                    '<div><h5 class="mb-1">' + title + '</h5>' +
                    (p.name ? '<div><strong>' + AC.esc(p.name) + '</strong> &middot; ' + AC.esc(p.no || '') + (p.batch ? ' &middot; ' + AC.esc(p.batch) : '') + '</div>' : '') +
                    (sub ? '<div>' + sub + '</div>' : '') + extra + payBtn + '</div></div>';
            }

            function addRow(d) {
                var p = d.player;
                var tr = document.createElement('tr');
                tr.id = 'att-' + d.attendance_id;
                tr.innerHTML =
                    '<td><div class="d-flex align-items-center gap-2"><img src="' + AC.esc(p.photo) +
                    '" class="avatar-sm rounded-circle" style="object-fit:cover" alt=""><div><h6 class="mb-0">' + AC.esc(p.name) +
                    '</h6><small class="text-muted">' + AC.esc(p.no || '') + '</small></div></div></td>' +
                    '<td class="d-none d-sm-table-cell">' + AC.esc(p.batch || '-') + '</td>' +
                    '<td>' + AC.esc(d.time) + '</td>' +
                    '<td class="text-end"><button type="button" class="act-ico act-undo undo" data-id="' + d.attendance_id + '" title="Undo (marked by mistake)"><iconify-icon icon="solar:undo-left-round-bold-duotone"></iconify-icon></button></td>';
                todayBody.insertBefore(tr, todayBody.firstChild);
            }

            function handle(d) {
                if (d.status === 'marked') {
                    beep(true);
                    addRow(d);
                    setCount(count + 1);
                } else {
                    beep(false);
                }
                showResult(d);
            }

            /* ---------- undo ---------- */
            todayBody.addEventListener('click', function(e) {
                var btn = e.target.closest('.undo');
                if (!btn) return;
                var id = btn.dataset.id;
                AC.act(undoTpl.replace('__ID__', id), {
                    method: 'DELETE',
                    confirm: {
                        title: 'Undo this attendance?',
                        text: 'The session will be given back to the player.',
                        button: 'Yes, undo'
                    },
                    onSuccess: function() {
                        var row = document.getElementById('att-' + id);
                        if (row) row.remove();
                        setCount(count - 1);
                    }
                });
            });

            /* ---------- camera scanner ---------- */
            var scanner = null,
                running = false,
                busy = false,
                lastToken = '',
                lastAt = 0;
            var btn = document.getElementById('btnCamera');
            var hint = document.getElementById('camHint');

            async function onScan(text) {
                var token = String(text || '').trim().toUpperCase();
                if (busy || !token) return;
                if (token === lastToken && Date.now() - lastAt < 5000) return; // same card held still
                busy = true;
                lastToken = token;
                lastAt = Date.now();

                var d = await AC.call(markUrl, {
                    body: {
                        token: token
                    }
                });
                handle(d);
                setTimeout(function() {
                    busy = false;
                }, 1200);
            }

            async function startCamera() {
                if (typeof Html5Qrcode === 'undefined') {
                    hint.innerHTML = '<span class="text-danger">Scanner could not load. Check the internet connection and refresh.</span>';
                    return;
                }
                scanner = scanner || new Html5Qrcode('reader');
                try {
                    await scanner.start({
                        facingMode: 'environment'
                    }, {
                        fps: 10,
                        qrbox: function(w, h) {
                            var s = Math.floor(Math.min(w, h) * 0.7);
                            return {
                                width: s,
                                height: s
                            };
                        }
                    }, onScan, function() {});
                    running = true;
                    btn.textContent = 'Stop Camera';
                    btn.className = 'btn btn-outline-danger btn-lg';
                    hint.textContent = 'Scanning... hold the card about 15-20 cm from the camera.';
                } catch (err) {
                    hint.innerHTML = '<span class="text-danger">Could not open the camera. Allow camera permission and make sure the site opens with https. (' +
                        AC.esc(String(err)) + ')</span>';
                }
            }

            async function stopCamera() {
                if (scanner && running) {
                    try {
                        await scanner.stop();
                    } catch (e) {}
                }
                running = false;
                btn.textContent = 'Start Camera';
                btn.className = 'btn btn-primary btn-lg';
            }

            btn.addEventListener('click', function() {
                running ? stopCamera() : startCamera();
            });

            /* ---------- manual search ---------- */
            var box = document.getElementById('searchBox');
            var list = document.getElementById('searchResults');

            box.addEventListener('input', AC.debounce(async function() {
                var q = box.value.trim();
                if (q.length < 2) {
                    list.innerHTML = '';
                    return;
                }
                var rows = await AC.call(searchUrl + '?q=' + encodeURIComponent(q), {
                    method: 'GET'
                });
                if (!Array.isArray(rows)) {
                    list.innerHTML = '<div class="list-group-item text-danger">' + AC.errors(rows) + '</div>';
                    return;
                }
                if (!rows.length) {
                    list.innerHTML = '<div class="list-group-item text-muted">No player found.</div>';
                    return;
                }
                list.innerHTML = rows.map(function(p) {
                    return '<div class="list-group-item d-flex align-items-center gap-2">' +
                        '<img src="' + AC.esc(p.photo) + '" class="avatar-sm rounded-circle" style="object-fit:cover" alt="">' +
                        '<div class="flex-grow-1"><h6 class="mb-0">' + AC.esc(p.name) + '</h6><small class="text-muted">' +
                        AC.esc(p.no || '') + (p.batch ? ' &middot; ' + AC.esc(p.batch) : '') + '</small></div>' +
                        '<span>' + p.badge + '</span>' +
                        '<button type="button" class="act-ico act-present mark-btn" data-id="' + p.id + '" title="Mark present"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon></button></div>';
                }).join('');
            }, 250));

            list.addEventListener('click', async function(e) {
                var b = e.target.closest('.mark-btn');
                if (!b) return;
                b.disabled = true;
                var d = await AC.call(markUrl, {
                    body: {
                        player_id: b.dataset.id
                    }
                });
                handle(d);
                box.value = '';
                list.innerHTML = '';
                box.focus();
            });

            // free the camera when leaving the page
            window.addEventListener('pagehide', function() {
                if (scanner && running) scanner.stop().catch(function() {});
            });
        })();
    </script>
@endsection