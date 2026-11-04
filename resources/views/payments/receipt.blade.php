{{-- Payment receipt (client, 3 Nov 2026), styled after the academy's paper invoice. Print / Save as PDF. --}}
@php
    $academy = config('academy.name', 'MS Football Academy');
    $methods = ['cash' => 'Cash', 'bank' => 'Bank'];
    if (!$isExtra && !isset($methods[$payment->method])) {
        $methods[$payment->method] = ucfirst($payment->method);
    }
    $ordinal = fn($n) => $n . (in_array($n % 100, [11, 12, 13]) ? 'TH' : (['TH', 'ST', 'ND', 'RD'][$n % 10] ?? 'TH'));
    $lkr = fn($v) => 'LKR ' . number_format($v, 0);
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt {{ $payment->receipt_no }} - {{ $player?->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ms-logo.png') }}">
    <style>
        :root {
            --navy: #0b3a75;
            --navy-dark: #07142b;
            --gold: #f5b301;
            --line: #2f5f9e;
            --text: #0b2a55;
            --muted: #5b6b85;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9eef5;
            font-family: "DIN Alternate", "Barlow Semi Condensed", "Arial Narrow", "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ---------- toolbar (not printed) ---------- */
        .bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            padding: 14px 16px;
        }

        .bar a,
        .bar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font: 600 14px/1 "Segoe UI", Roboto, Arial, sans-serif;
            padding: 11px 16px;
            border-radius: 9px;
            border: 0;
            cursor: pointer;
            text-decoration: none;
        }

        .b-print {
            background: var(--navy);
            color: #fff;
        }

        .b-wa {
            background: #16a34a;
            color: #fff;
        }

        .b-back {
            background: #fff;
            color: #334155;
            box-shadow: inset 0 0 0 1px #cbd5e1;
        }

        .hint {
            text-align: center;
            font: 12.5px "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--muted);
            margin: -4px 16px 12px;
        }

        /* ---------- the receipt ---------- */
        .sheet {
            position: relative;
            width: 100%;
            max-width: 780px;
            margin: 0 auto 28px;
            padding: 34px 40px 30px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0) 70%, rgba(22, 101, 52, .10) 100%),
                repeating-linear-gradient(90deg, rgba(22, 101, 52, .035) 0 60px, rgba(22, 101, 52, .07) 60px 120px),
                #fbfcfe;
            border: 1px solid #c9d4e5;
            overflow: hidden;
        }

        /* faint shield behind the table */
        .sheet::before {
            content: "";
            position: absolute;
            inset: 140px 0 90px;
            background: url("{{ asset('images/ms-logo.png') }}") center / auto 78% no-repeat;
            opacity: .06;
            pointer-events: none;
        }

        .head {
            position: relative;
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 8px;
        }

        .head img {
            width: 104px;
            height: auto;
            flex: 0 0 auto;
        }

        .title {
            flex: 1;
            min-width: 0;
        }

        .title h1 {
            margin: 0;
            font-family: "Times New Roman", Georgia, serif;
            font-weight: 700;
            font-size: 44px;
            line-height: 1;
            letter-spacing: .5px;
            color: var(--navy);
        }

        .title .row2 {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .pill {
            display: inline-block;
            background: var(--gold);
            color: var(--navy);
            font-weight: 700;
            font-size: 26px;
            letter-spacing: .5px;
            padding: 4px 16px;
            border-radius: 9px;
        }

        .meta {
            position: relative;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 6px 18px;
            font-size: 14px;
            color: var(--muted);
            margin: 12px 0 10px;
        }

        .meta b {
            color: var(--text);
        }

        table {
            position: relative;
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th {
            background: var(--navy);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: .4px;
            padding: 9px 10px;
            text-transform: uppercase;
        }

        td {
            border: 1px solid var(--line);
            padding: 9px 10px;
            vertical-align: top;
            background: rgba(255, 255, 255, .55);
        }

        .who b {
            display: block;
            font-size: 18px;
        }

        .who span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .weeks {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .weeks li {
            padding: 4px 0;
            border-bottom: 1px solid rgba(47, 95, 158, .35);
            font-weight: 600;
            font-size: 14px;
        }

        .weeks li:last-child {
            border-bottom: 0;
        }

        .weeks small {
            font-weight: 400;
            color: var(--muted);
        }

        .wk td {
            vertical-align: middle;
        }

        .wkname {
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
        }

        .wkdates {
            font-size: 14px;
            line-height: 1.55;
            white-space: nowrap;
        }

        .muted {
            color: var(--muted);
            font-size: 12.5px;
        }

        .mk {
            display: inline-block;
            margin-left: 4px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            padding: 2px 4px;
            border-radius: 3px;
            color: #fff;
            vertical-align: 1px;
        }

        .mk.p {
            background: #16a34a;
        }

        .mk.a {
            background: #dc2626;
        }

        .amt {
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
        }

        .how {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .4px;
        }

        .how div {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 2px 0;
        }

        .box {
            width: 14px;
            height: 14px;
            border: 1.5px solid var(--line);
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            line-height: 1;
            color: #fff;
        }

        .box.on {
            background: var(--navy);
            border-color: var(--navy);
        }

        .foot td {
            vertical-align: middle;
        }

        .note {
            font-size: 14px;
        }

        .note b {
            letter-spacing: .4px;
        }

        .sum td {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .4px;
            text-align: right;
        }

        .sum td.v {
            text-align: left;
            white-space: nowrap;
        }

        .sum .bal {
            color: #b91c1c;
        }

        .sign {
            position: relative;
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin-top: 52px;
        }

        .sign div {
            flex: 1;
            text-align: center;
        }

        .sign .ln {
            border-top: 2px dotted var(--line);
            padding-top: 6px;
            font-weight: 600;
            letter-spacing: .5px;
        }

        .sign .by {
            font-size: 15px;
            min-height: 20px;
            margin-bottom: 4px;
        }

        .thanks {
            position: relative;
            text-align: center;
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 18px;
        }

        /* ---------- phones ---------- */
        @media (max-width: 600px) {
            .sheet {
                padding: 20px 14px 18px;
            }

            .head {
                gap: 10px;
            }

            .head img {
                width: 62px;
            }

            .title h1 {
                font-size: 24px;
            }

            .pill {
                font-size: 15px;
                padding: 3px 10px;
            }

            table {
                font-size: 13px;
            }

            th {
                font-size: 11px;
                padding: 7px 5px;
            }

            td {
                padding: 7px 6px;
            }

            .who b {
                font-size: 15px;
            }

            .wkdates,
            .wkname {
                font-size: 12px;
                white-space: normal;
            }

            .amt {
                font-size: 16px;
            }

            .sum td {
                font-size: 13px;
            }

            .sign {
                gap: 16px;
                margin-top: 36px;
            }
        }

        /* ---------- print ---------- */
        @page {
            size: A5 portrait;
            margin: 8mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .bar,
            .hint {
                display: none !important;
            }

            .sheet {
                margin: 0;
                max-width: none;
                border: 0;
                padding: 6mm 6mm 4mm;
            }

            .head img {
                width: 80px;
            }

            .title h1 {
                font-size: 32px;
            }

            .pill {
                font-size: 19px;
            }

            table {
                font-size: 12.5px;
            }
        }
    </style>
</head>

<body>
    <div class="bar">
        <button type="button" class="b-print" onclick="window.print()">&#128424; Print / Save as PDF</button>
        @if ($wa)
            <a class="b-wa" href="{{ $wa }}" target="_blank" rel="noopener">&#9990; Send on WhatsApp</a>
        @endif
        @if ($player)
            <a class="b-back" href="{{ route('admin.players.show', $player) }}">&larr; Back to profile</a>
        @endif
    </div>
    <p class="hint">To send it as a file: Print &rarr; "Save as PDF", then share the PDF.</p>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('images/ms-logo.png') }}" alt="MS Football Academy">
            <div class="title">
                <h1>MS FOOTBALL</h1>
                <div class="row2">
                    <h1>ACADEMY</h1>
                    <span class="pill">RECEIPT</span>
                </div>
            </div>
        </div>

        <div class="meta">
            <span>Receipt No: <b>{{ $payment->receipt_no }}</b></span>
            <span>Date: <b>{{ $payment->paid_at->format('d M Y') }}</b></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:30%">Student name</th>
                    <th style="width:17%">Weeks</th>
                    <th style="width:30%">Date</th>
                    <th style="width:23%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $nRows = max(1, count($weekRows)); @endphp
                @for ($w = 0; $w < $nRows; $w++)
                    <tr class="wk">
                        @if ($w === 0)
                            <td class="who" rowspan="{{ $nRows }}">
                                <b>{{ $player?->name ?? 'Player removed' }}</b>
                                @if ($player)
                                    <span>{{ $player->player_no }} &middot; {{ $player->age_group }}</span>
                                    <span>{{ $isExtra ? 'Extra days (free)' : $plan['label'] }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="wkname">
                            @if ($isExtra)
                                {{ $payment->sessions }} FREE {{ $payment->sessions === 1 ? 'DAY' : 'DAYS' }}
                            @else
                                {{ $ordinal($w + 1) }} WEEK
                            @endif
                        </td>
                        <td class="wkdates">
                            @foreach ($weekRows[$w] ?? [] as $d)
                                <div>
                                    @if ($d['mark'] === 'B')
                                        <span class="muted">Before system</span>
                                    @elseif ($d['date'])
                                        {{ $d['date']->format('D d/m/Y') }}
                                        @if ($d['mark'] === 'P')
                                            <span class="mk p">&#10003;</span>
                                        @elseif ($d['mark'] === 'A')
                                            <span class="mk a">AB</span>
                                        @endif
                                    @else
                                        <span class="muted">Next practice</span>
                                    @endif
                                </div>
                            @endforeach
                            @if ($isExtra)
                                <span class="muted">Given {{ $payment->paid_at->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        @if ($w === 0)
                            <td rowspan="{{ $nRows }}">
                                <div class="amt">{{ $isExtra ? 'FREE' : $lkr($paid) }}</div>
                                <div class="muted" style="font-size:12px;margin-top:2px">Paid {{ $payment->paid_at->format('d/m/Y') }}</div>
                                @unless ($isExtra)
                                    <div class="how">
                                        @foreach ($methods as $key => $label)
                                            <div><span class="box {{ $payment->method === $key ? 'on' : '' }}">{!! $payment->method === $key ? '&#10003;' : '' !!}</span>
                                                {{ strtoupper($label) }}</div>
                                        @endforeach
                                    </div>
                                @endunless
                            </td>
                        @endif
                    </tr>
                @endfor
            </tbody>
            <tbody class="foot">
                <tr class="sum">
                    <td colspan="2" rowspan="3" class="note" style="text-align:left;vertical-align:top;font-weight:400">
                        <b>NOTE :</b>
                        {{ $payment->sessions }} {{ $payment->sessions === 1 ? 'session' : 'sessions' }}
                        ({{ $weeks }} {{ $weeks === 1 ? 'week' : 'weeks' }}){{ $payment->note ? ' - ' . $payment->note : '.' }}
                    </td>
                    <td>TOTAL</td>
                    <td class="v">{{ $isExtra ? 'FREE' : $lkr($total) }}</td>
                </tr>
                <tr class="sum">
                    <td>PAID</td>
                    <td class="v">{{ $isExtra ? '-' : $lkr($paid) }}</td>
                </tr>
                <tr class="sum">
                    <td>BALANCE</td>
                    <td class="v {{ $balance > 0 ? 'bal' : '' }}">{{ $isExtra ? '-' : $lkr($balance) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="sign">
            <div>
                <div class="by">{{ $payment->receiver?->name }}</div>
                <div class="ln">SIGNATURE</div>
            </div>
            <div>
                <div class="by">{{ $payment->paid_at->format('d/m/Y') }}</div>
                <div class="ln">DATE</div>
            </div>
        </div>

        <p class="thanks">Thank you! &middot; {{ $academy }}</p>
    </div>
</body>

</html>
