@extends('layouts.vertical', ['subtitle' => 'Users'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Users', 'subtitle' => 'Staff Logins'])
    @include('partials.js-helpers')

    <style>



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
                            <th class="d-none d-sm-table-cell">Role</th>
                            <th class="d-none d-lg-table-cell">Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr id="user-{{ $user->id }}">
                                @php
                                    $roleColor = ['Super Admin' => '#7c3aed', 'Admin' => '#2563eb', 'Front Desk' => '#0891b2'][$user->type] ?? '#6b7280';
                                @endphp
                                <td>
                                    <span class="ac-person">
                                        <img src="{{ $user->image_path ? asset($user->image_path) : asset('/images/users/avatar-6.jpg') }}" alt="">
                                        <span class="min-w-0">
                                            <span class="nm d-block">{{ $user->name }}</span>
                                            <span class="sub d-block d-md-none text-break">{{ $user->email }}</span>
                                            <span class="d-block d-sm-none mt-1">{{ \App\Models\Player::softBadge($roleColor, $user->type, 96) }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $user->email }}</td>
                                <td class="d-none d-sm-table-cell">{{ \App\Models\Player::softBadge($roleColor, $user->type, 96) }}</td>
                                <td class="d-none d-lg-table-cell">{{ $user->updated_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <div class="ac-actions">
                                        @if ($user->type !== 'Super Admin' && $user->id !== auth()->id())
                                            <button type="button" class="btn btn-sm btn-soft-danger delete-user"
                                                data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon><span class="ac-hide-xs">Delete</span>
                                            </button>
                                        @else
                                            <span class="text-muted small">{{ $user->id === auth()->id() ? 'You' : '-' }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    {{-- Add user --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" id="userForm" method="POST" action="{{ route('admin.users.store') }}"
                enctype="multipart/form-data" autocomplete="off">
                @csrf
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
                AC.act("{{ url('admin/users') }}/" + b.dataset.id, {
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
@endsection