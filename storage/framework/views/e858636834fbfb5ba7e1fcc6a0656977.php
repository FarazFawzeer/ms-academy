<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Coaches', 'subtitle' => $coach ? 'Edit' : 'Add New'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo e($coach ? 'Edit ' . $coach->name : 'New Coach'); ?></h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="coachForm" action="<?php echo e($coach ? route('admin.coaches.update', $coach) : route('admin.coaches.store')); ?>"
                method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo e($coach?->name); ?>"
                            placeholder="Ex: Nuwan Silva" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($coach?->phone); ?>"
                            inputmode="tel" placeholder="Ex: 0771234567">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="daily_rate" class="form-label">Daily Salary (LKR)</label>
                        <input type="number" id="daily_rate" name="daily_rate" class="form-control" min="0" step="0.01"
                            value="<?php echo e($coach?->daily_rate ?? ''); ?>" placeholder="Ex: 3000" required>
                        <div class="form-text">Paid for each day the coach works. If you change it later, days already
                            recorded keep their old amount.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nic" class="form-label">NIC <span class="text-muted">(optional)</span></label>
                        <input type="text" id="nic" name="nic" class="form-control" value="<?php echo e($coach?->nic); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="photo" class="form-label">Photo <span class="text-muted">(optional)</span></label>
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <img id="photoPreview" src="<?php echo e($coach?->photo_url); ?>" alt=""
                            class="rounded border <?php echo e($coach?->photo_path ? '' : 'd-none'); ?>"
                            style="width:90px;height:90px;object-fit:cover">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo e(route('admin.coaches.index')); ?>" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?php echo e($coach ? 'Save Changes' : 'Save Coach'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        AC.form(document.getElementById('coachForm'));

        document.getElementById('photo').addEventListener('change', function() {
            var f = this.files[0],
                img = document.getElementById('photoPreview');
            if (!f) return;
            img.src = URL.createObjectURL(f);
            img.classList.remove('d-none');
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => $coach ? 'Edit Coach' : 'Add Coach'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/coaches/form.blade.php ENDPATH**/ ?>