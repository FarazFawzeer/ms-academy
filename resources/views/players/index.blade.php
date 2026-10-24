@extends('layouts.vertical', ['subtitle' => 'Players'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'All Players'])
    @include('partials.js-helpers')

    @php $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']); @endphp

    <style>
        /* Filter bar: right aligned, one clean row on desktop, stacked on mobile */
        .pl-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
        }

        .pl-filters .form-control,
        .pl-filters .form-select {
            height: 38px;
            font-size: 14px;
        }

        .pl-filters .pl-search {
            position: relative;
            flex: 0 1 280px;
            min-width: 200px;
        }

        .pl-filters .pl-search iconify-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #8a94a6;
            pointer-events: none;
        }

        .pl-filters .pl-search .form-control {
            padding-left: 34px;
        }

        .pl-filters .pl-select {
            flex: 0 0 160px;
        }

        .pl-clear {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 500;
            color: #dc3545;
            text-decoration: none;
            white-space: nowrap;
        }

        .pl-clear:hover {
            text-decoration: underline;
            color: #b02a37;
        }

        @media (max-width: 767.98px) {
            .pl-filters {
                justify-content: stretch;
            }

            .pl-filters .pl-search {
                flex: 1 1 100%;
            }

            .pl-filters .pl-select {
                flex: 1 1 calc(50% - .25rem);
            }
        }

    </style>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0">Player List</h5>
                    <p class="card-subtitle mb-0">{{ $players->total() }} player(s) found.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary ac-btn">
                        <iconify-icon icon="solar:layers-outline"></iconify-icon> Age Groups
                    </a>
                    <a href="{{ route('admin.players.print', array_filter(['group' => $group])) }}"
                        class="btn btn-outline-secondary">
                        <iconify-icon icon="solar:printer-outline" class="me-1"></iconify-icon> Print ID Cards
                    </a>
                    <a href="{{ route('admin.players.create') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:user-plus-outline" class="me-1"></iconify-icon> Add Player
                    </a>
                </div>
            </div>

            {{-- Auto filter: no Search button. Typing waits 0.5s, dropdowns apply instantly. --}}
            <form method="GET" id="playerFilter" class="pl-filters">
                <div class="pl-search">
                    <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                    <input type="search" name="q" id="plSearch" value="{{ request('q') }}" class="form-control"
                        placeholder="Search name, ID or phone" autocomplete="off">
                </div>

                <select name="group" class="form-select pl-select" onchange="this.form.submit()">
                    <option value="">All age groups</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g }}" {{ $group === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>

                <select name="days" class="form-select pl-select" onchange="this.form.submit()">
                    <option value="">All training days</option>
                    @foreach ($slots as $key => $s)
                        <option value="{{ $key }}" {{ $days === $key ? 'selected' : '' }}>Trains on {{ $s['label'] }}</option>
                        <option value="{{ $key }}-only" {{ $days === $key . '-only' ? 'selected' : '' }}>{{ $s['label'] }} only</option>
                    @endforeach
                    <option value="both" {{ $days === 'both' ? 'selected' : '' }}>Both days</option>
                </select>

                <select name="status" class="form-select pl-select" onchange="this.form.submit()">
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                </select>

                @if (request('q') || $group || $days || (request('status') && request('status') !== 'active'))
                    <a href="{{ route('admin.players.index') }}" class="pl-clear">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player</th>
                            <th class="d-none d-sm-table-cell">Group</th>
                            <th class="d-none d-lg-table-cell">Days</th>
                            <th class="d-none d-xl-table-cell">Parent</th>
                            <th class="d-none d-sm-table-cell">Sessions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($players as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.players.show', $p) }}" class="ac-person ac-photo-sm-only">
                                        <img src="{{ $p->photo_url }}" alt="" loading="lazy">
                                        <span class="min-w-0">
                                            <span class="nm d-block">{{ $p->name }}
                                                @unless ($p->is_active)
                                                    <span class="badge ms-1" style="background:#6b72801a;color:#4b5563">Inactive</span>
                                                @endunless
                                            </span>
                                            <span class="sub d-block">{{ $p->player_no }} &middot; {{ $p->age }} yrs</span>
                                            {{-- phones: group and sessions under the name --}}
                                            <span class="d-flex d-sm-none gap-1 mt-1" data-balance="{{ $p->id }}">{{ $p->balanceBadge() }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="d-none d-sm-table-cell">{{ $p->ageGroupBadge() }}</td>
                                <td class="d-none d-lg-table-cell">{{ $p->daysBadge() }}</td>
                                <td class="d-none d-xl-table-cell">
                                    {{ $p->guardian_name }}<br>
                                    <small class="text-muted">{{ $p->guardian_phone }}</small>
                                </td>
                                <td class="d-none d-sm-table-cell" data-balance="{{ $p->id }}">{{ $p->balanceBadge() }}</td>
                                <td>
                                    <div class="ac-actions">
                                        <button type="button" class="btn btn-sm btn-soft-success"
                                            onclick="openPayment({{ $p->id }}, @js($p->name), @js($p->plan()))">
                                            <iconify-icon icon="solar:wallet-money-outline"></iconify-icon><span>Pay</span>
                                        </button>
                                        <a href="{{ route('admin.players.edit', $p) }}" class="btn btn-sm btn-soft-primary" title="Edit details">
                                            <iconify-icon icon="solar:pen-outline"></iconify-icon><span class="ac-hide-xs">Edit</span>
                                        </a>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-soft-secondary ac-more"
                                                data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions"
                                                title="More actions">
                                                <iconify-icon icon="solar:menu-dots-bold"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.players.show', $p) }}">
                                                        <iconify-icon icon="solar:user-id-outline"></iconify-icon> View profile</a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.players.cards', ['ids' => $p->id]) }}"
                                                        target="_blank">
                                                        <iconify-icon icon="solar:printer-outline"></iconify-icon> Print ID card</a></li>
                                                @if ($isAdmin)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><button type="button" class="dropdown-item text-danger btn-delete-player"
                                                            data-id="{{ $p->id }}" data-name="{{ $p->name }}">
                                                            <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon> Delete player</button></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No players found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $players->withQueryString()->links() }}
            </div>
        </div>
    </div>

    @include('players._payment-modal')

    <script>
        (function() {
            var form = document.getElementById('playerFilter');
            var input = document.getElementById('plSearch');
            var timer;

            // Admin: delete a player for good (asks first, suggests deactivating instead)
            document.querySelectorAll('.btn-delete-player').forEach(function(b) {
                b.addEventListener('click', function() {
                    deletePlayer(b.dataset.id, b.dataset.name, function() {
                        location.reload();
                    });
                });
            });

            // Auto search 0.5s after the user stops typing
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    form.submit();
                }, 500);
            });

            // Enter key submits immediately
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timer);
                }
            });

            // After the page reloads, keep the cursor in the search box
            if (input.value) {
                input.focus();
                var len = input.value.length;
                input.setSelectionRange(len, len);
            }
        })();
    </script>
@endsection