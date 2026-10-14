<meta charset="utf-8" />
<title>{{ $subtitle ?? 'Dashboard' }} - {{ config('academy.name', 'MS Football Academy') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="{{ config('academy.name', 'MS Football Academy') }} management system: QR attendance, player profiles, fees, renewal reminders and coach salary." />
<meta name="author" content="{{ config('academy.name', 'MS Football Academy') }}" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
{{-- Private staff system: keep it out of search engines --}}
<meta name="robots" content="noindex, nofollow" />
<meta name="theme-color" content="#0f766e">

<!-- App favicon -->
<link rel="icon" type="image/png" href="{{ asset('images/ms-logo.png') }}">
<link rel="shortcut icon" href="{{ asset('images/ms-logo.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/ms-logo.png') }}">