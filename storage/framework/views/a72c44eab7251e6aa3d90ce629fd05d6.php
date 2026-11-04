<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Reminders', 'subtitle' => 'WhatsApp'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>




    </style>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Renewal Reminders to Send</h5>
            <p class="card-subtitle mb-0">
                Added automatically when one week of practice is left (2 sessions for Friday &amp; Sunday players,
                1 for one-day players). Tap <strong>Send</strong>: WhatsApp opens with the message already typed -
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
                            <th class="d-none d-sm-table-cell">Sessions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pendingBody">
                        <?php $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr id="rem-<?php echo e($p->id); ?>">
                                <td>
                                    <a href="<?php echo e(route('admin.players.show', $p)); ?>" class="ac-person">
                                        <img src="<?php echo e($p->photo_url); ?>" alt="">
                                        <span>
                                            <span class="nm d-block"><?php echo e($p->name); ?></span>
                                            <span class="sub d-block"><?php echo e($p->player_no); ?> &middot; <?php echo e($p->age_group); ?></span>
                                            <span class="d-block d-sm-none mt-1" data-balance="<?php echo e($p->id); ?>"><?php echo e($p->balanceBadge()); ?></span>
                                        </span>
                                    </a>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo e($p->guardian_name); ?><br><small class="text-muted"><?php echo e($p->guardian_phone); ?></small>
                                </td>
                                <td class="d-none d-sm-table-cell" data-balance="<?php echo e($p->id); ?>"><?php echo e($p->balanceBadge()); ?></td>
                                <td>
                                    <div class="ac-actions">
                                        <?php if($p->whatsappLink()): ?>
                                            <a href="<?php echo e($p->whatsappLink()); ?>" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-success wa-send" data-id="<?php echo e($p->id); ?>">
                                                <iconify-icon icon="ic:baseline-whatsapp"></iconify-icon><span>Send</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-danger small">No valid number</span>
                                        <?php endif; ?>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-soft-secondary ac-more"
                                                data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions" title="More actions">
                                                <iconify-icon icon="solar:menu-dots-bold"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button type="button" class="dropdown-item"
                                                        onclick="openPayment(<?php echo e($p->id); ?>, <?php echo \Illuminate\Support\Js::from($p->name)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($p->plan())->toHtml() ?>)">
                                                        <iconify-icon icon="solar:wallet-money-outline"></iconify-icon> Already paid - receive payment</button></li>
                                                <li><a class="dropdown-item" href="<?php echo e(route('admin.players.show', $p)); ?>">
                                                        <iconify-icon icon="solar:user-id-outline"></iconify-icon> View profile</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><button type="button" class="dropdown-item text-danger dismiss" data-id="<?php echo e($p->id); ?>">
                                                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Remove without sending</button></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <tr id="emptyRow" class="<?php echo e($pending->isEmpty() ? '' : 'd-none'); ?>">
                            <td colspan="4" class="text-center text-muted py-4">No reminders waiting. 🎉</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if($recent->isNotEmpty()): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recently Sent</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><a href="<?php echo e(route('admin.players.show', $r)); ?>"><?php echo e($r->name); ?></a>
                                        <small class="text-muted">· <?php echo e($r->guardian_name); ?></small>
                                    </td>
                                    <td class="text-end text-muted"><?php echo e($r->last_reminded_at->diffForHumans()); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php echo $__env->make('players._payment-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        (function() {
            var sentTpl = "<?php echo e(route('admin.reminders.sent', ['player' => '__ID__'])); ?>";
            var dismissTpl = "<?php echo e(route('admin.reminders.dismiss', ['player' => '__ID__'])); ?>";
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

            // payment undone: the reminder may be back - reload the list
            document.addEventListener('payment:undone', function() {
                setTimeout(function() {
                    location.reload();
                }, 700);
            });
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Reminders'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/reminders/index.blade.php ENDPATH**/ ?>