<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'Profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']); ?>

    <div class="row g-3">
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo e($player->photo_url); ?>" alt="<?php echo e($player->name); ?>" class="rounded-circle mb-3"
                        style="width:120px;height:120px;object-fit:cover">
                    <h4 class="mb-1"><?php echo e($player->name); ?></h4>
                    <p class="text-muted mb-2"><?php echo e($player->player_no); ?></p>
                    <div class="d-flex justify-content-center gap-2 mb-2">
                        <?php echo e($player->ageGroupBadge()); ?> <?php echo e($player->daysBadge()); ?>

                    </div>
                    <span class="badge bg-<?php echo e($player->is_active ? 'success' : 'secondary'); ?>"
                        id="statusBadge"><?php echo e($player->is_active ? 'Active' : 'Inactive'); ?></span>

                    <hr>

                    <div class="text-start">
                        <p class="mb-2"><span class="text-muted">Date of birth</span><br>
                            <?php echo e($player->dob->format('d M Y')); ?> (<?php echo e($player->age); ?> yrs)
                            <?php if($nextMove): ?>
                                <br><small class="text-muted">Moves to the next age group on
                                    <?php echo e($nextMove->format('d M Y')); ?></small>
                            <?php endif; ?>
                        </p>
                        <p class="mb-2"><span class="text-muted">Parent / Guardian</span><br>
                            <?php echo e($player->guardian_name); ?></p>
                        <p class="mb-2"><span class="text-muted">Parent number</span><br>
                            <a href="tel:<?php echo e($player->guardian_phone); ?>"><?php echo e($player->guardian_phone); ?></a>
                            <?php if($player->whatsapp_number): ?>
                                · <a href="https://wa.me/<?php echo e($player->whatsapp_number); ?>" target="_blank"
                                    rel="noopener">WhatsApp</a>
                            <?php endif; ?>
                        </p>
                        <?php if($player->phone): ?>
                            <p class="mb-2"><span class="text-muted">Player phone</span><br><?php echo e($player->phone); ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><span class="text-muted">Joined</span><br>
                            <?php echo e($player->joined_at?->format('d M Y') ?? '-'); ?></p>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="<?php echo e(route('admin.players.cards', ['ids' => $player->id])); ?>" target="_blank"
                            class="btn btn-dark">
                            <iconify-icon icon="solar:printer-outline" class="me-1"></iconify-icon> Print ID Card
                        </a>
                        <a href="<?php echo e(route('admin.players.edit', $player)); ?>" class="btn btn-outline-primary">Edit Profile</a>
                        <button type="button" class="btn btn-outline-<?php echo e($player->is_active ? 'danger' : 'success'); ?>"
                            id="toggleBtn"><?php echo e($player->is_active ? 'Deactivate Player' : 'Activate Player'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="text-muted mb-1">Paid sessions left</p>
                            <h2 class="mb-0" data-balance="<?php echo e($player->id); ?>"><?php echo e($player->balanceBadge()); ?></h2>
                        </div>
                        <div>
                            <p class="text-muted mb-1">Total sessions attended</p>
                            <h2 class="mb-0"><?php echo e($totalAttended); ?></h2>
                        </div>
                        <button type="button" class="btn btn-success btn-lg"
                            onclick="openPayment(<?php echo e($player->id); ?>, <?php echo \Illuminate\Support\Js::from($player->name)->toHtml() ?>)">
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
                                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($pay->paid_at->format('d M Y, h:i A')); ?></td>
                                        <td><?php echo e(number_format($pay->amount)); ?></td>
                                        <td>+<?php echo e($pay->sessions); ?></td>
                                        <td class="d-none d-sm-table-cell"><?php echo e(ucfirst($pay->method)); ?></td>
                                        <td class="d-none d-md-table-cell"><?php echo e($pay->note ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No payments yet.</td>
                                    </tr>
                                <?php endif; ?>
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
                                <?php $__empty_1 = true; $__currentLoopData = $attendance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($a->attendance_date->format('D, d M Y')); ?></td>
                                        <td><?php echo e($a->created_at?->format('h:i A')); ?></td>
                                        <td class="d-none d-sm-table-cell"><?php echo e($a->age_group ?? '-'); ?></td>
                                        <td><?php echo e($a->method === 'qr' ? 'QR scan' : 'Manual'); ?>

                                            <?php if($a->is_extra): ?>
                                                <span class="badge ms-1" style="background:#2563eb1a;color:#2563eb">Extra day</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No attendance yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('players._payment-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        // new payment -> reload so the payment table and balance are both fresh
        document.addEventListener('payment:saved', function() {
            setTimeout(function() {
                location.reload();
            }, 900);
        });

        document.getElementById('toggleBtn').addEventListener('click', function() {
            var active = <?php echo e($player->is_active ? 'true' : 'false'); ?>;
            AC.act("<?php echo e(route('admin.players.toggle', $player)); ?>", {
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => $player->name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/players/show.blade.php ENDPATH**/ ?>