<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => $player ? 'Edit' : 'Add New'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        // new players train on every regular day unless the admin unticks one
        $chosen = $player ? $player->days : array_keys($slots);
    ?>

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
            <h5 class="card-title mb-0"><?php echo e($player ? 'Edit ' . $player->name : 'New Player'); ?></h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="playerForm"
                action="<?php echo e($player ? route('admin.players.update', $player) : route('admin.players.store')); ?>"
                method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Player Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo e($player?->name); ?>"
                            placeholder="Ex: Kasun Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control"
                            value="<?php echo e($player?->dob?->format('Y-m-d')); ?>" max="<?php echo e(today()->subDay()->toDateString()); ?>"
                            required>
                        <div class="form-text" id="groupHint">
                            Age group is set automatically from the date of birth.
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Training Days</label>
                    <div class="day-pick">
                        <?php $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <input type="checkbox" name="days[]" value="<?php echo e($key); ?>" id="day_<?php echo e($key); ?>"
                                <?php echo e(in_array($key, $chosen, true) ? 'checked' : ''); ?>>
                            <label for="day_<?php echo e($key); ?>">
                                <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
                                <span><?php echo e($s['label']); ?><br>
                                    <small><?php echo e(\Carbon\Carbon::parse($s['start'])->format('g:i A')); ?>

                                        - <?php echo e(\Carbon\Carbon::parse($s['end'])->format('g:i A')); ?></small></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="form-text">Pick one or both. The player can only be marked present on these days.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="guardian_name" class="form-label">Parent / Guardian Name</label>
                        <input type="text" id="guardian_name" name="guardian_name" class="form-control"
                            value="<?php echo e($player?->guardian_name); ?>" placeholder="Ex: Dilan Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="guardian_phone" class="form-label">Parent WhatsApp Number</label>
                        <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control"
                            value="<?php echo e($player?->guardian_phone); ?>" placeholder="Ex: 0771234567" inputmode="tel" required>
                        <div class="form-text">Reminders are sent to this number.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Player Phone <span class="text-muted">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($player?->phone); ?>"
                            inputmode="tel">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="joined_at" class="form-label">Joined On</label>
                        <input type="date" id="joined_at" name="joined_at" class="form-control"
                            value="<?php echo e(($player?->joined_at ?? today())->format('Y-m-d')); ?>">
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
                        <img id="photoPreview" src="<?php echo e($player?->photo_url); ?>" alt=""
                            class="rounded border <?php echo e($player?->photo_path ? '' : 'd-none'); ?>"
                            style="width:110px;height:110px;object-fit:cover">
                    </div>
                </div>

                <?php if (! ($player)): ?>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="collect_fee" name="collect_fee"
                            value="1" checked>
                        <label class="form-check-label" for="collect_fee">
                            First payment received now
                            (LKR <?php echo e(number_format(config('academy.fee'))); ?> for <?php echo e(config('academy.sessions_per_package')); ?>

                            sessions)
                        </label>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo e($player ? route('admin.players.show', $player) : route('admin.players.index')); ?>"
                        class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo e($player ? 'Save Changes' : 'Save Player'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        AC.form(document.getElementById('playerForm'));

        document.getElementById('photo').addEventListener('change', function() {
            var f = this.files[0],
                img = document.getElementById('photoPreview');
            if (!f) return;
            img.src = URL.createObjectURL(f);
            img.classList.remove('d-none');
        });

        // Live age group preview (same bands as config/academy.php)
        (function() {
            var bands = <?php echo json_encode(\App\Support\AgeGroup::bands(), 15, 512) ?>;
            var senior = <?php echo json_encode(\App\Support\AgeGroup::senior(), 15, 512) ?>;
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => $player ? 'Edit Player' : 'Add Player'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/players/form.blade.php ENDPATH**/ ?>