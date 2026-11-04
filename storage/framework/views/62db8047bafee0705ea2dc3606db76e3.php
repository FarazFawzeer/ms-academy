<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'Scan'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']); ?>

    <style>
        /* ---------- today banner ---------- */
        .sc-today {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 14px;
        }

        .sc-today > iconify-icon {
            font-size: 26px;
            flex-shrink: 0;
        }

        .sc-today.on {
            background: rgba(15, 118, 110, .08);
            border: 1px solid rgba(15, 118, 110, .3);
            color: #0f766e;
        }

        .sc-today.off {
            background: rgba(180, 83, 9, .08);
            border: 1px solid rgba(180, 83, 9, .3);
            color: #b45309;
        }

        .sc-today small {
            display: block;
            color: #64748b;
        }

        .sc-count {
            margin-left: auto;
            text-align: right;
            line-height: 1.1;
        }

        .sc-count b {
            font-size: 22px;
            display: block;
        }

        .sc-count small {
            font-size: 11px;
        }

        /* ---------- camera with result flash on top ---------- */
        .sc-cam {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background: #0f172a;
            aspect-ratio: 1 / 1;
            max-height: 62vh;
            width: 100%;
            margin: 0 auto;
        }

        #reader {
            border: 0 !important;
            width: 100%;
            height: 100%;
        }

        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        #reader img[alt="Info icon"],
        #reader__dashboard_section_csr,
        #reader__header_message {
            display: none !important;
        }

        .sc-idle {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #94a3b8;
            text-align: center;
            padding: 20px;
        }

        .sc-idle iconify-icon {
            font-size: 56px;
            color: #475569;
        }

        .sc-flash {
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            color: #fff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .3);
            transform: translateY(130%);
            transition: transform .18s ease;
            z-index: 5;
        }

        .sc-flash.show {
            transform: translateY(0);
        }

        .sc-flash.ok {
            background: #15803d;
        }

        .sc-flash.warn {
            background: #b45309;
        }

        .sc-flash.bad {
            background: #b91c1c;
        }

        .sc-flash img {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, .7);
            flex-shrink: 0;
        }

        .sc-flash .t {
            font-weight: 700;
            font-size: 17px;
            line-height: 1.2;
        }

        .sc-flash .s {
            font-size: 13px;
            opacity: .92;
        }

        .sc-frame {
            position: absolute;
            inset: 18%;
            border: 3px solid rgba(255, 255, 255, .55);
            border-radius: 18px;
            pointer-events: none;
            z-index: 4;
            display: none;
        }

        .sc-cam.running .sc-frame {
            display: block;
        }

        .sc-cam.running .sc-idle {
            display: none;
        }

        /* ---------- result card (stays until the next scan) ---------- */
        .sc-result {
            margin-top: 12px;
        }

        .sc-result .alert {
            margin: 0;
        }

        /* ---------- search ---------- */
        .sc-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
        }

        .sc-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .not-today {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            background: #b453091a;
            color: #b45309;
            margin-left: 4px;
            white-space: nowrap;
        }

        .sc-list .ac-person img {
            width: 34px;
            height: 34px;
        }
    </style>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <?php if($session): ?>
                        <div class="sc-today on">
                            <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
                            <div><strong><?php echo e($session->label); ?></strong>
                                <small><?php echo e($session->time_text); ?> &middot; <?php echo e($expected); ?> players train today</small></div>
                            <div class="sc-count"><b id="countBig"><?php echo e($today->count()); ?></b><small>marked</small></div>
                        </div>
                    <?php else: ?>
                        <div class="sc-today off">
                            <iconify-icon icon="solar:calendar-minimalistic-bold-duotone"></iconify-icon>
                            <div><strong>No practice today</strong>
                                <small><?php echo e($isAdmin ? 'You can still mark an extra session.' : 'Attendance is marked on practice days only.'); ?></small>
                            </div>
                            <div class="sc-count"><b id="countBig"><?php echo e($today->count()); ?></b><small>marked</small></div>
                        </div>
                    <?php endif; ?>

                    <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                        <li class="nav-item"><a class="nav-link active ac-btn" data-bs-toggle="pill" href="#tabScan" role="tab">
                                <iconify-icon icon="solar:qr-code-outline"></iconify-icon> Scan cards</a></li>
                        <li class="nav-item"><a class="nav-link ac-btn" data-bs-toggle="pill" href="#tabSearch" role="tab" id="searchTab">
                                <iconify-icon icon="solar:magnifer-outline"></iconify-icon> Search name</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabScan" role="tabpanel">
                            <div class="sc-cam" id="cam">
                                <div id="reader"></div>
                                <div class="sc-frame"></div>
                                <div class="sc-idle">
                                    <iconify-icon icon="solar:camera-outline"></iconify-icon>
                                    <div>Press <strong class="text-white">Start scanning</strong>, then hold each card in
                                        front of the camera. Players are marked one after another, no need to tap.</div>
                                </div>
                                <div class="sc-flash" id="flash" role="status" aria-live="polite"></div>
                            </div>
                            <div class="d-grid mt-3">
                                <button type="button" id="btnCamera" class="btn btn-primary btn-lg ac-btn">
                                    <iconify-icon icon="solar:play-circle-outline"></iconify-icon> Start scanning
                                </button>
                            </div>
                            <p class="text-muted small mt-2 mb-0" id="camHint">Works on the phone camera (needs https). Keep the
                                card about 15-20 cm away.</p>
                        </div>

                        <div class="tab-pane fade" id="tabSearch" role="tabpanel">
                            <input type="search" id="searchBox" class="form-control form-control-lg"
                                placeholder="Name, ID (MS-0012) or phone" autocomplete="off" enterkeyhint="done">
                            <div class="form-text">Press Enter to mark when only one player matches.</div>
                            <div id="searchResults" class="list-group mt-2"></div>
                        </div>
                    </div>

                    <div id="result" class="sc-result"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="card-title mb-0">Today - <?php echo e(today()->format('l, d M Y')); ?></h5>
                        <p class="card-subtitle mb-0">Newest first. Undo if someone was marked by mistake.</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fs-12" id="todayBadge"><?php echo e($today->count()); ?> marked</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive sc-list">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Player</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th>Time</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="todayBody">
                                <?php $__currentLoopData = $today; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr id="att-<?php echo e($a->id); ?>">
                                        <td>
                                            <span class="ac-person">
                                                <img src="<?php echo e($a->photo_path ? asset($a->photo_path) : asset('images/users/avatar-6.jpg')); ?>" alt="">
                                                <span>
                                                    <span class="nm d-block"><?php echo e($a->name); ?></span>
                                                    <span class="sub d-block"><?php echo e($a->player_no); ?>

                                                        <?php if($a->is_extra): ?>
                                                            <span class="not-today">Extra</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </span>
                                            </span>
                                        </td>
                                        <td class="d-none d-sm-table-cell"><?php echo e($a->age_group ?? '-'); ?></td>
                                        <td class="text-nowrap"><?php echo e(\Carbon\Carbon::parse($a->created_at)->format('h:i A')); ?></td>
                                        <td>
                                            <div class="ac-actions">
                                                <button type="button" class="btn btn-sm btn-soft-danger undo" data-id="<?php echo e($a->id); ?>">
                                                    <iconify-icon icon="solar:undo-left-round-outline"></iconify-icon><span>Undo</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <tr id="emptyRow" class="<?php echo e($today->isEmpty() ? '' : 'd-none'); ?>">
                                    <td colspan="4" class="text-center text-muted py-4">No one has been marked yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('players._payment-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function() {
            var markUrl = "<?php echo e(route('admin.attendance.mark')); ?>";
            var searchUrl = "<?php echo e(route('admin.attendance.search')); ?>";
            var undoTpl = "<?php echo e(route('admin.attendance.destroy', ['attendance' => '__ID__'])); ?>";
            var avatar = "<?php echo e(asset('images/users/avatar-6.jpg')); ?>";

            var $ = function(id) {
                return document.getElementById(id);
            };
            var resultBox = $('result'),
                todayBody = $('todayBody'),
                emptyRow = $('emptyRow');
            var count = <?php echo e($today->count()); ?>;

            function setCount(n) {
                count = Math.max(0, n);
                $('todayBadge').textContent = count + ' marked';
                $('countBig').textContent = count;
                emptyRow.classList.toggle('d-none', count > 0);
            }

            /* ---------- feedback: beep + vibrate ---------- */
            var audioCtx;

            function beep(kind) {
                try {
                    audioCtx = audioCtx || new(window.AudioContext || window.webkitAudioContext)();
                    var o = audioCtx.createOscillator(),
                        g = audioCtx.createGain();
                    o.connect(g);
                    g.connect(audioCtx.destination);
                    o.frequency.value = kind === 'ok' ? 1046 : (kind === 'warn' ? 520 : 260);
                    g.gain.value = 0.09;
                    o.start();
                    o.stop(audioCtx.currentTime + (kind === 'ok' ? 0.12 : 0.32));
                } catch (e) {}
                if (navigator.vibrate) navigator.vibrate(kind === 'ok' ? 70 : [120, 60, 120]);
            }

            /* ---------- what a server answer means ---------- */
            function describe(d) {
                var p = d.player || {};
                var r = {
                    kind: 'bad',
                    title: AC.esc(d.message || 'Not recognised'),
                    sub: '',
                    extra: ''
                };

                if (d.status === 'marked') {
                    r.kind = d.payment_due ? 'warn' : 'ok';
                    r.title = d.extra ? 'Marked (extra day)' : 'Marked present';
                    r.sub = d.payment_due ? 'PAYMENT DUE - collect the fee' :
                        'Session ' + d.session + ' of ' + d.total + ' &middot; ' + d.balance + ' left';
                    if (d.reminder) r.extra = '<span class="badge bg-warning text-dark mt-1">Renewal reminder queued</span>';
                } else if (d.status === 'duplicate') {
                    r.kind = 'warn';
                    r.title = 'Already marked today';
                } else if (d.status === 'inactive') {
                    r.kind = 'warn';
                    r.title = 'Player is inactive';
                } else if (d.status === 'wrong_day' || d.status === 'no_practice') {
                    r.kind = 'warn';
                    r.title = d.status === 'wrong_day' ? 'Not this player\'s training day' : 'No practice today';
                    r.sub = AC.esc(d.message || '');
                }
                r.name = p.name || '';
                r.line = [p.no, p.group, p.days].filter(Boolean).map(AC.esc).join(' &middot; ');
                return r;
            }

            /* big flash on top of the camera (hides by itself) */
            var flashTimer;

            function flash(d) {
                var r = describe(d),
                    p = d.player || {},
                    el = $('flash');
                el.className = 'sc-flash ' + r.kind;
                el.innerHTML = (p.name ? '<img src="' + AC.esc(p.photo || avatar) + '" alt="">' : '') +
                    '<div><div class="t">' + (p.name ? AC.esc(p.name) : r.title) + '</div><div class="s">' +
                    (p.name ? r.title + (r.sub ? ' &middot; ' + r.sub : '') : r.sub) + '</div></div>';
                void el.offsetWidth;
                el.classList.add('show');
                clearTimeout(flashTimer);
                flashTimer = setTimeout(function() {
                    el.classList.remove('show');
                }, r.kind === 'ok' ? 1800 : 3200);
            }

            /* result card under the camera / search (stays, with action buttons) */
            function showResult(d) {
                var r = describe(d),
                    p = d.player || {};
                var cls = {
                    ok: 'success',
                    warn: 'warning',
                    bad: 'danger'
                } [r.kind];
                var btns = '';

                if (d.status === 'marked' && (d.payment_due || d.reminder)) {
                    btns += '<button type="button" class="btn btn-sm btn-success ac-btn" id="btnPay">' +
                        '<iconify-icon icon="solar:wallet-money-outline"></iconify-icon> Receive payment</button>';
                }
                if ((d.status === 'wrong_day' || d.status === 'no_practice') && d.can_override) {
                    btns += '<button type="button" class="btn btn-sm btn-warning ac-btn" id="btnForce">' +
                        '<iconify-icon icon="solar:add-circle-outline"></iconify-icon> Mark anyway (extra day)</button>';
                } else if (d.status === 'wrong_day' || d.status === 'no_practice') {
                    btns += '<div class="small mt-1">Ask an Admin if this is a make-up class.</div>';
                }

                resultBox.innerHTML =
                    '<div class="alert alert-' + cls + ' d-flex align-items-center gap-3">' +
                    (p.name ? '<img src="' + AC.esc(p.photo || avatar) + '" class="rounded-circle flex-shrink-0" style="width:56px;height:56px;object-fit:cover" alt="">' : '') +
                    '<div class="flex-grow-1"><div class="fw-bold">' + r.title + '</div>' +
                    (p.name ? '<div><strong>' + AC.esc(p.name) + '</strong> &middot; ' + r.line + '</div>' : '') +
                    (r.sub ? '<div>' + r.sub + '</div>' : '') + r.extra +
                    (btns ? '<div class="d-flex flex-wrap gap-2 mt-2">' + btns + '</div>' : '') + '</div></div>';

                var pay = $('btnPay');
                if (pay) pay.addEventListener('click', function() {
                    openPayment(p.id, p.name, {
                        label: p.days,
                        fee: p.fee,
                        sessions: p.plan
                    });
                });
            }

            function addRow(d) {
                var p = d.player;
                var tr = document.createElement('tr');
                tr.id = 'att-' + d.attendance_id;
                tr.innerHTML =
                    '<td><span class="ac-person"><img src="' + AC.esc(p.photo || avatar) + '" alt=""><span><span class="nm d-block">' +
                    AC.esc(p.name) + '</span><span class="sub d-block">' + AC.esc(p.no || '') +
                    (d.extra ? ' <span class="not-today">Extra</span>' : '') + '</span></span></span></td>' +
                    '<td class="d-none d-sm-table-cell">' + AC.esc(p.group || '-') + '</td>' +
                    '<td class="text-nowrap">' + AC.esc(d.time) + '</td>' +
                    '<td><div class="ac-actions"><button type="button" class="btn btn-sm btn-soft-danger undo" data-id="' + d.attendance_id +
                    '"><iconify-icon icon="solar:undo-left-round-outline"></iconify-icon><span>Undo</span></button></div></td>';
                todayBody.insertBefore(tr, todayBody.firstChild);
            }

            function handle(d, fromCamera) {
                var kind = describe(d).kind;
                beep(kind);
                if (d.status === 'marked') {
                    addRow(d);
                    setCount(count + 1);
                }
                if (fromCamera) flash(d);
                showResult(d);
            }

            // last request, so an Admin can repeat it with force=1 ("Mark anyway")
            var lastBody = null;

            async function mark(body, fromCamera) {
                lastBody = body;
                handle(await AC.call(markUrl, {
                    body: body
                }), fromCamera);
            }

            resultBox.addEventListener('click', async function(e) {
                var b = e.target.closest('#btnForce');
                if (!b || !lastBody) return;
                b.disabled = true;
                await mark(Object.assign({}, lastBody, {
                    force: 1
                }), false);
            });

            /* ---------- undo ---------- */
            todayBody.addEventListener('click', function(e) {
                var btn = e.target.closest('.undo');
                if (!btn) return;
                var id = btn.dataset.id;
                AC.act(undoTpl.replace('__ID__', id), {
                    method: 'DELETE',
                    confirm: {
                        title: 'Undo this attendance?',
                        text: 'The session is given back to the player.',
                        button: 'Yes, undo'
                    },
                    onSuccess: function() {
                        var row = $('att-' + id);
                        if (row) row.remove();
                        setCount(count - 1);
                    }
                });
            });

            /* ---------- non-stop camera scanner ---------- */
            var scanner = null,
                running = false,
                inFlight = false,
                seen = {}, // token -> time last sent (same card held still is ignored for a few seconds)
                wakeLock = null;
            var btn = $('btnCamera'),
                hint = $('camHint'),
                cam = $('cam');

            async function onScan(text) {
                var token = String(text || '').trim().toUpperCase();
                var now = Date.now();
                if (!token || inFlight) return;
                if (seen[token] && now - seen[token] < 6000) return;
                seen[token] = now;
                inFlight = true;
                try {
                    await mark({
                        token: token
                    }, true);
                } finally {
                    inFlight = false;
                }
            }

            async function keepAwake() {
                try {
                    if ('wakeLock' in navigator) wakeLock = await navigator.wakeLock.request('screen');
                } catch (e) {}
            }

            function setBtn(on) {
                btn.innerHTML = on ?
                    '<iconify-icon icon="solar:stop-circle-outline"></iconify-icon> Stop scanning' :
                    '<iconify-icon icon="solar:play-circle-outline"></iconify-icon> Start scanning';
                btn.className = (on ? 'btn btn-outline-danger' : 'btn btn-primary') + ' btn-lg ac-btn';
            }

            async function startCamera() {
                if (typeof Html5Qrcode === 'undefined') {
                    hint.innerHTML = '<span class="text-danger">Scanner could not load. Check the internet connection and refresh.</span>';
                    return;
                }
                scanner = scanner || new Html5Qrcode('reader', {
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true // much faster on Android Chrome
                    },
                    verbose: false
                });
                btn.disabled = true;
                try {
                    await scanner.start({
                        facingMode: 'environment'
                    }, {
                        fps: 15,
                        qrbox: function(w, h) {
                            var s = Math.floor(Math.min(w, h) * 0.75);
                            return {
                                width: s,
                                height: s
                            };
                        },
                        aspectRatio: 1
                    }, onScan, function() {});
                    running = true;
                    cam.classList.add('running');
                    setBtn(true);
                    hint.textContent = 'Scanning... show the cards one after another.';
                    keepAwake();
                    try {
                        localStorage.setItem('ac-scan-on', '1');
                    } catch (e) {}
                } catch (err) {
                    hint.innerHTML = '<span class="text-danger">Could not open the camera. Allow camera permission and make sure the site opens with https. (' +
                        AC.esc(String(err)) + ')</span>';
                } finally {
                    btn.disabled = false;
                }
            }

            async function stopCamera() {
                if (scanner && running) {
                    try {
                        await scanner.stop();
                    } catch (e) {}
                }
                running = false;
                cam.classList.remove('running');
                setBtn(false);
                hint.textContent = 'Scanner stopped.';
                if (wakeLock) wakeLock.release().catch(function() {});
                try {
                    localStorage.removeItem('ac-scan-on');
                } catch (e) {}
            }

            btn.addEventListener('click', function() {
                running ? stopCamera() : startCamera();
            });

            // screen lock released when the tab was hidden: ask again when it comes back
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible' && running) keepAwake();
            });

            // was scanning before a refresh: start again straight away
            try {
                if (localStorage.getItem('ac-scan-on') === '1') startCamera();
            } catch (e) {}

            /* ---------- manual search ---------- */
            var box = $('searchBox');
            var list = $('searchResults');
            var lastRows = [];

            function drawRows(rows) {
                lastRows = rows;
                if (!rows.length) {
                    list.innerHTML = '<div class="list-group-item text-muted">No player found.</div>';
                    return;
                }
                list.innerHTML = rows.map(function(p) {
                    return '<div class="list-group-item sc-item">' +
                        '<img src="' + AC.esc(p.photo) + '" alt="">' +
                        '<div class="flex-grow-1 min-w-0"><div class="fw-semibold">' + AC.esc(p.name) + '</div><small class="text-muted">' +
                        AC.esc(p.no || '') + (p.group ? ' &middot; ' + AC.esc(p.group) : '') +
                        (p.not_today ? '<span class="not-today">Not today</span>' : '') + '</small>' +
                        '<div class="mt-1">' + p.badge + '</div></div>' +
                        '<button type="button" class="btn btn-success ac-btn mark-btn" data-id="' + p.id + '">' +
                        '<iconify-icon icon="solar:check-circle-outline"></iconify-icon><span>Present</span></button></div>';
                }).join('');
            }

            box.addEventListener('input', AC.debounce(async function() {
                var q = box.value.trim();
                if (q.length < 2) {
                    list.innerHTML = '';
                    lastRows = [];
                    return;
                }
                var rows = await AC.call(searchUrl + '?q=' + encodeURIComponent(q), {
                    method: 'GET'
                });
                if (!Array.isArray(rows)) {
                    list.innerHTML = '<div class="list-group-item text-danger">' + AC.errors(rows) + '</div>';
                    return;
                }
                drawRows(rows);
            }, 200));

            async function markFromSearch(id) {
                await mark({
                    player_id: id
                }, false);
                box.value = '';
                list.innerHTML = '';
                lastRows = [];
                box.focus();
            }

            // Enter marks the player when exactly one matches
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && lastRows.length === 1) {
                    e.preventDefault();
                    markFromSearch(lastRows[0].id);
                }
            });

            list.addEventListener('click', function(e) {
                var b = e.target.closest('.mark-btn');
                if (!b) return;
                b.disabled = true;
                markFromSearch(b.dataset.id);
            });

            $('searchTab').addEventListener('shown.bs.tab', function() {
                box.focus();
            });

            // free the camera when leaving the page
            window.addEventListener('pagehide', function() {
                if (scanner && running) scanner.stop().catch(function() {});
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Scan Attendance'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/attendance/scan.blade.php ENDPATH**/ ?>