@extends('layouts.vertical', ['subtitle' => $player ? 'Edit Player' : 'Add Player'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => $player ? 'Edit' : 'Add New'])
    @include('partials.js-helpers')

    @php
        // new players train on every regular day unless the admin unticks one
        $chosen = $player ? $player->days : array_keys($slots);
    @endphp

    <style>
        /* Training day toggles: big, tappable, clear on/off */
        .day-pick {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .day-pick input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .day-pick label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1 1 0;
            min-width: 120px;
            max-width: 220px;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            user-select: none;
            transition: all .12s ease;
        }

        .day-pick label iconify-icon {
            font-size: 20px;
        }

        .day-pick label small {
            font-weight: 400;
            color: #94a3b8;
        }

        .day-pick input:checked + label {
            border-color: #0f766e;
            background: rgba(15, 118, 110, .08);
            color: #0f766e;
        }

        .day-pick input:checked + label small {
            color: #0f766e;
        }

        .day-pick input:focus-visible + label {
            outline: 2px solid #0f766e;
            outline-offset: 2px;
        }

        .pl-plan {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #0f766e;
            background: rgba(15, 118, 110, .08);
            border-radius: 8px;
            padding: 6px 10px;
        }

        /* Payment on joining: three choice tiles */
        .pay-pick {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .pay-pick input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .pay-pick label {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 100%;
            padding: 10px 12px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            user-select: none;
            transition: all .12s ease;
            margin: 0;
        }

        .pay-pick label iconify-icon {
            font-size: 24px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .pay-pick strong {
            display: block;
            font-size: 14px;
            color: #334155;
        }

        .pay-pick small {
            display: block;
            font-size: 12px;
            color: #94a3b8;
        }

        .pay-pick input:checked + label {
            border-color: #0f766e;
            background: rgba(15, 118, 110, .06);
        }

        .pay-pick input:checked + label iconify-icon,
        .pay-pick input:checked + label strong {
            color: #0f766e;
        }

        .pay-pick input:focus-visible + label {
            outline: 2px solid #0f766e;
            outline-offset: 2px;
        }

        @media (max-width: 575.98px) {
            .pay-pick {
                grid-template-columns: 1fr;
            }
        }

        .before-box {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 14px;
            background: rgba(100, 116, 139, .04);
        }

        .stepper {
            max-width: 200px;
        }

        .stepper .btn {
            width: 44px;
            font-size: 18px;
            font-weight: 600;
        }

        .used-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 10px;
        }

        .grp-pill {
            display: inline-block;
            min-width: 64px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(15, 118, 110, .10);
            color: #0f766e;
        }
    </style>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $player ? 'Edit ' . $player->name : 'New Player' }}</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="playerForm"
                action="{{ $player ? route('admin.players.update', $player) : route('admin.players.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Player Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $player?->name }}"
                            placeholder="Ex: Kasun Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" data-picker="dob"
                            value="{{ $player?->dob?->format('Y-m-d') }}" max="{{ today()->subDay()->toDateString() }}"
                            required>
                        <div class="form-text" id="groupHint">
                            Age group is set automatically from the date of birth.
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Training Days</label>
                    <div class="day-pick">
                        @foreach ($slots as $key => $s)
                            <input type="checkbox" name="days[]" value="{{ $key }}" id="day_{{ $key }}"
                                {{ in_array($key, $chosen, true) ? 'checked' : '' }}>
                            <label for="day_{{ $key }}">
                                <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
                                <span>{{ $s['label'] }}<br>
                                    <small>{{ \Carbon\Carbon::parse($s['start'])->format('g:i A') }}
                                        - {{ \Carbon\Carbon::parse($s['end'])->format('g:i A') }}</small></span>
                            </label>
                        @endforeach
                    </div>
                    <div class="form-text">Pick one or both. The player can only be marked present on these days.</div>
                    <div class="pl-plan mt-2" id="planHint"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guardian_name" class="form-label">Parent / Guardian Name</label>
                        <input type="text" id="guardian_name" name="guardian_name" class="form-control"
                            value="{{ $player?->guardian_name }}" placeholder="Ex: Dilan Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guardian_phone" class="form-label">Parent WhatsApp Number</label>
                        <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control"
                            value="{{ $player?->guardian_phone }}" placeholder="Ex: 0771234567" inputmode="tel" required>
                        <div class="form-text">Reminders are sent to this number.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Player Phone <span class="text-muted">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="{{ $player?->phone }}"
                            inputmode="tel">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="joined_at" class="form-label">Joined On</label>
                        <input type="date" id="joined_at" name="joined_at" class="form-control"
                            value="{{ ($player?->joined_at ?? today())->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                        <div class="form-text">On a phone you can take the photo with the camera. It is resized automatically.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <img id="photoPreview" src="{{ $player?->photo_url }}" alt=""
                            class="rounded border {{ $player?->photo_path ? '' : 'd-none' }}"
                            style="width:110px;height:110px;object-fit:cover">
                    </div>
                </div>

                @unless ($player)
                    {{-- Payment on joining --}}
                    <div class="mb-4">
                        <label class="form-label d-block">Payment on joining</label>
                        <div class="pay-pick">
                            <input type="radio" name="payment_mode" id="pm_now" value="now" checked>
                            <label for="pm_now"><iconify-icon icon="solar:wallet-money-outline"></iconify-icon>
                                <span><strong>Paying now</strong><small class="pm-fee"></small></span></label>

                            <input type="radio" name="payment_mode" id="pm_before" value="before">
                            <label for="pm_before"><iconify-icon icon="solar:history-outline"></iconify-icon>
                                <span><strong>Already paid before</strong><small>Some sessions already done</small></span></label>

                            <input type="radio" name="payment_mode" id="pm_none" value="none">
                            <label for="pm_none"><iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                                <span><strong>Not paid yet</strong><small>Collect later</small></span></label>
                        </div>

                        {{-- player who paid before the system started --}}
                        <div class="before-box mt-3 d-none" id="beforeBox">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="paid_on">Paid on <span class="text-muted">(optional)</span></label>
                                    <input type="date" id="paid_on" name="paid_on" class="form-control"
                                        max="{{ today()->toDateString() }}">
                                    <div class="form-text">Leave empty if you don't know. Today's date is used.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="used">Sessions already done</label>
                                    <div class="input-group stepper">
                                        <button type="button" class="btn btn-outline-secondary" data-step="-1" aria-label="One less">&minus;</button>
                                        <input type="number" id="used" name="used" class="form-control text-center" value="0"
                                            min="0" max="8" inputmode="numeric">
                                        <button type="button" class="btn btn-outline-secondary" data-step="1" aria-label="One more">+</button>
                                    </div>
                                    <div class="form-text" id="usedHint"></div>
                                </div>
                            </div>
                            <div id="usedDates" class="mt-3"></div>
                        </div>
                    </div>
                @endunless

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ $player ? route('admin.players.show', $player) : route('admin.players.index') }}"
                        class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ $player ? 'Save Changes' : 'Save Player' }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        AC.form(document.getElementById('playerForm'));

        // Fee plan follows the training days; "Already paid before" asks how many sessions are done
        (function() {
            var plans = @json(config('academy.plans'));
            var order = @json(array_keys($slots));
            var hint = document.getElementById('planHint');
            var dayBoxes = document.querySelectorAll('input[name="days[]"]');
            var before = document.getElementById('beforeBox');
            var used = document.getElementById('used');
            var datesBox = document.getElementById('usedDates');

            function plan() {
                var picked = order.filter(function(k) {
                    return document.getElementById('day_' + k).checked;
                });
                return plans[picked.join(',')] || null;
            }

            function showPlan() {
                var p = plan();
                hint.innerHTML = p ? '<iconify-icon icon="solar:tag-price-outline"></iconify-icon> ' + AC.esc(p.label) +
                    ': LKR ' + AC.fmt(p.fee) + ' for ' + p.sessions + ' sessions' : '';
                hint.classList.toggle('d-none', !p);
                document.querySelectorAll('.pm-fee').forEach(function(el) {
                    el.textContent = p ? 'LKR ' + AC.fmt(p.fee) + ' today' : '';
                });
                if (used) {
                    used.max = p ? p.sessions : 8;
                    if (+used.value > +used.max) used.value = used.max;
                    drawDates();
                }
            }

            // one optional date field per session already done (typed dates are kept when the number changes)
            function drawDates() {
                if (!datesBox) return;
                var n = Math.max(0, Math.min(+used.value || 0, +used.max));
                var p = plan();
                document.getElementById('usedHint').textContent = p ?
                    (p.sessions - n) + ' of ' + p.sessions + ' sessions will be left' : '';

                var keep = Array.from(datesBox.querySelectorAll('input[name="used_dates[]"]')).map(function(i) {
                    return i.value;
                });
                if (!n) {
                    datesBox.innerHTML = '';
                    return;
                }
                var html = '<label class="form-label d-block">Dates of those sessions <span class="text-muted">(optional - fill the ones you know)</span></label><div class="used-grid">';
                for (var i = 0; i < n; i++) {
                    html += '<div><input type="date" name="used_dates[]" class="form-control" max="{{ today()->toDateString() }}" ' +
                        'value="' + AC.esc(keep[i] || '') + '" placeholder="Session ' + (i + 1) + '" aria-label="Session ' + (i + 1) + ' date"></div>';
                }
                datesBox.innerHTML = html + '</div>';
                if (window.ACDate) ACDate.enhance(datesBox);
            }

            dayBoxes.forEach(function(b) {
                b.addEventListener('change', showPlan);
            });

            document.querySelectorAll('input[name=payment_mode]').forEach(function(r) {
                r.addEventListener('change', function() {
                    before.classList.toggle('d-none', r.value !== 'before' || !r.checked);
                });
            });

            if (used) {
                used.addEventListener('input', drawDates);
                document.querySelectorAll('.stepper [data-step]').forEach(function(b) {
                    b.addEventListener('click', function() {
                        used.value = Math.max(0, Math.min(+used.max, (+used.value || 0) + (+b.dataset.step)));
                        drawDates();
                    });
                });
            }

            showPlan();
        })();

        document.getElementById('photo').addEventListener('change', function() {
            var f = this.files[0],
                img = document.getElementById('photoPreview');
            if (!f) return;
            img.src = URL.createObjectURL(f);
            img.classList.remove('d-none');
        });

        // Live age group preview (same bands as config/academy.php)
        (function() {
            var bands = @json(\App\Support\AgeGroup::bands());
            var senior = @json(\App\Support\AgeGroup::senior());
            var dob = document.getElementById('dob');
            var hint = document.getElementById('groupHint');

            function show() {
                if (!dob.value) return;
                var b = new Date(dob.value + 'T00:00:00'),
                    t = new Date();
                var age = t.getFullYear() - b.getFullYear();
                if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) age--;
                if (age < 0) return;
                var g = senior;
                for (var k in bands) {
                    if (age <= bands[k]) {
                        g = k;
                        break;
                    }
                }
                hint.innerHTML = 'Age ' + age + ' &rarr; <span class="grp-pill">' + AC.esc(g) +
                    '</span> <span class="text-muted">(automatic, moves up on birthdays)</span>';
            }

            dob.addEventListener('change', show);
            show();
        })();
    </script>
@endsection
