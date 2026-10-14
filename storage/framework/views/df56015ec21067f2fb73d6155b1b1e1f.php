

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Users', 'subtitle' => 'Staff Logins'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.js-helpers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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

        .act-del {
            color: #dc2626;
        }

        .role-badge {
            display: inline-block;
            width: 100px;
            text-align: center;
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            padding: 6px 0;
            border-radius: 6px;
            white-space: nowrap;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="card-title mb-0">System Users</h5>
                <p class="card-subtitle mb-0">People who can log in. Front Desk scans, adds players and receives payments. Admin can also manage everything else.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                <iconify-icon icon="solar:user-plus-outline" class="me-1"></iconify-icon> Add User
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th>Role</th>
                            <th class="d-none d-lg-table-cell">Updated</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr id="user-<?php echo e($user->id); ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo e($user->image_path ? asset($user->image_path) : asset('/images/users/avatar-6.jpg')); ?>"
                                            alt="" class="avatar-sm rounded-circle" style="object-fit:cover">
                                        <div>
                                            <h6 class="mb-0"><?php echo e($user->name); ?></h6>
                                            <small class="text-muted d-md-none"><?php echo e($user->email); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo e($user->email); ?></td>
                                <td>
                                    <?php
                                        $roleColor = ['Super Admin' => '#7c3aed', 'Admin' => '#2563eb', 'Front Desk' => '#0891b2'][$user->type] ?? '#6b7280';
                                    ?>
                                    <span class="role-badge" style="background:<?php echo e($roleColor); ?>"><?php echo e($user->type); ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo e($user->updated_at->format('d M Y, h:i A')); ?></td>
                                <td class="text-end">
                                    <?php if($user->type !== 'Super Admin' && $user->id !== auth()->id()): ?>
                                        <button type="button" class="act-ico act-del delete-user" title="Delete user"
                                            data-id="<?php echo e($user->id); ?>" data-name="<?php echo e($user->name); ?>">
                                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?php echo e($users->links()); ?>

            </div>
        </div>
    </div>

    
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" id="userForm" method="POST" action="<?php echo e(route('admin.users.store')); ?>"
                enctype="multipart/form-data" autocomplete="off">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="message"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="u_name" class="form-label">Full Name</label>
                            <input type="text" id="u_name" name="name" class="form-control" placeholder="Ex: John Doe" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="u_email" class="form-label">Email Address</label>
                            <input type="email" id="u_email" name="email" class="form-control" placeholder="Ex: john@gmail.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="u_pass" class="form-label">Password</label>
                            <input type="password" id="u_pass" name="password" class="form-control" placeholder="At least 6 characters" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="u_pass2" class="form-label">Re-enter Password</label>
                            <input type="password" id="u_pass2" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="u_type" class="form-label">User Type</label>
                            <select id="u_type" name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Front Desk">Front Desk</option>
                                <option value="Admin">Admin</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="u_img" class="form-label">Profile Image <span class="text-muted">(optional)</span></label>
                            <input type="file" id="u_img" name="image_path" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        AC.form(document.getElementById('userForm'), {
            onSuccess: function() {
                AC.toast('User created');
                setTimeout(function() {
                    location.reload();
                }, 700);
            }
        });

        document.querySelectorAll('.delete-user').forEach(function(b) {
            b.addEventListener('click', function() {
                AC.act("<?php echo e(url('admin/users')); ?>/" + b.dataset.id, {
                    method: 'DELETE',
                    confirm: {
                        title: 'Delete ' + b.dataset.name + '?',
                        text: 'This person will no longer be able to log in.',
                        button: 'Yes, delete'
                    },
                    onSuccess: function() {
                        document.getElementById('user-' + b.dataset.id).remove();
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Users'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Nexas\MS Academy\ms-academy\resources\views/admin/users.blade.php ENDPATH**/ ?>