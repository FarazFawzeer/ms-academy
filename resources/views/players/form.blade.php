@extends('layouts.vertical', ['subtitle' => $player ? 'Edit Player' : 'Add Player'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => $player ? 'Edit' : 'Add New'])
    @include('partials.js-helpers')

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
                        <input type="date" id="dob" name="dob" class="form-control"
                            value="{{ $player?->dob?->format('Y-m-d') }}" max="{{ today()->subDay()->toDateString() }}"
                            required>
                    </div>
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
                        <label for="batch_id" class="form-label">Batch</label>
                        <select id="batch_id" name="batch_id" class="form-select">
                            <option value="">No batch yet</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b->id }}" {{ $player?->batch_id == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Player Phone <span class="text-muted">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="{{ $player?->phone }}"
                            inputmode="tel">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                        <div class="form-text">On a phone you can take the photo with the camera. It is resized automatically.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="joined_at" class="form-label">Joined On</label>
                        <input type="date" id="joined_at" name="joined_at" class="form-control"
                            value="{{ ($player?->joined_at ?? today())->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <img id="photoPreview" src="{{ $player?->photo_url }}" alt=""
                        class="rounded border {{ $player?->photo_path ? '' : 'd-none' }}"
                        style="width:110px;height:110px;object-fit:cover">
                </div>

                @unless ($player)
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="collect_fee" name="collect_fee"
                            value="1" checked>
                        <label class="form-check-label" for="collect_fee">
                            First payment received now
                            (LKR {{ number_format(config('academy.fee')) }} for {{ config('academy.sessions_per_package') }}
                            sessions)
                        </label>
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

        document.getElementById('photo').addEventListener('change', function() {
            var f = this.files[0],
                img = document.getElementById('photoPreview');
            if (!f) return;
            img.src = URL.createObjectURL(f);
            img.classList.remove('d-none');
        });
    </script>
@endsection
