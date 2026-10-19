<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Player ID Cards - {{ config('academy.name') }}</title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef0f4;
            color: #222;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #1f3864;
            color: #fff;
        }

        .toolbar button,
        .toolbar a {
            background: #2e7d32;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 10px 18px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar a {
            background: #4a5f8a;
        }

        .toolbar small {
            opacity: .8;
        }

        .sheet {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 4mm;
            justify-content: center;
            padding: 12px 0 30px;
        }

        /* CR80 card: 85.6 x 54 mm. Everything inside is sized in mm so print is exact. */
        .idc {
            --navy: #14284b;
            --navy2: #1f3f73;
            --gold: #f2b705;
            position: relative;
            width: 85.6mm;
            height: 54mm;
            border-radius: 3mm;
            overflow: hidden;
            background: #fff;
            color: #1b2437;
            break-inside: avoid;
            page-break-inside: avoid;
            outline: .2mm solid #cfcfcf;
            outline-offset: .6mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* header band */
        .idc .hd {
            height: 12mm;
            padding: 0 4mm;
            display: flex;
            align-items: center;
            gap: 3mm;
            background: linear-gradient(100deg, var(--navy), var(--navy2));
            border-bottom: .8mm solid var(--gold);
            color: #fff;
        }

        .idc .hd .logo {
            height: 8.4mm;
            width: 8.4mm;
            object-fit: contain;
            background: #fff;
            border-radius: 50%;
            padding: .5mm;
            flex: none;
        }

        .idc .hd .ttl {
            line-height: 1.2;
        }

        .idc .hd .ttl b {
            display: block;
            font-size: 3.4mm;
            letter-spacing: .25mm;
        }

        .idc .hd .ttl span {
            display: block;
            font-size: 2.1mm;
            letter-spacing: .5mm;
            color: var(--gold);
            font-weight: bold;
        }

        /* body: photo | details | QR */
        .idc .bd {
            display: flex;
            align-items: flex-start;
            gap: 3.2mm;
            padding: 3.4mm 4mm 0;
            height: 36.2mm;
        }

        .idc .photo {
            flex: none;
            width: 22mm;
            height: 28mm;
            object-fit: cover;
            border: .6mm solid var(--navy);
            border-radius: 1.5mm;
            background: #e9edf3;
        }

        .idc .info {
            flex: 1;
            min-width: 0;
        }

        .idc .nm {
            font-size: 3.7mm;
            font-weight: bold;
            line-height: 1.12;
            color: var(--navy);
            max-height: 8.4mm;
            overflow: hidden;
            padding-bottom: 1.2mm;
            margin-bottom: 1.4mm;
            border-bottom: .3mm solid #d5dbe6;
        }

        .idc .row {
            margin-bottom: 1.5mm;
        }

        .idc .row small {
            display: block;
            font-size: 1.85mm;
            text-transform: uppercase;
            letter-spacing: .2mm;
            color: #6b768b;
        }

        .idc .row span {
            display: block;
            font-size: 2.8mm;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .idc .qrbox {
            flex: none;
            width: 22mm;
            text-align: center;
        }

        .idc .qr {
            width: 22mm;
            height: 22mm;
            padding: 1mm;
            border: .3mm solid #d5dbe6;
            border-radius: 1.5mm;
        }

        .idc .qr svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .idc .no {
            margin-top: 1.3mm;
            padding: .7mm 0;
            font-size: 3mm;
            font-weight: bold;
            letter-spacing: .3mm;
            color: #fff;
            background: var(--navy);
            border-radius: 1mm;
        }

        /* footer */
        .idc .ft {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 5.8mm;
            padding: 0 4mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f4f9;
            border-top: .3mm solid #d5dbe6;
            font-size: 2.1mm;
            color: #4a5670;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <div>
            <strong>{{ $players->count() }} ID card(s)</strong><br>
            <small>Print on A4 at 100% scale (turn OFF "fit to page"). 10 cards per sheet. Cut along the grey lines.</small>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.players.print') }}">Change selection</a>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="sheet">
        @forelse ($players as $p)
            <div class="idc">
                <div class="hd">
                    <img class="logo" src="{{ asset('images/ms-logo.png') }}" alt="">
                    <div class="ttl">
                        <b>{{ strtoupper(config('academy.name')) }}</b>
                        <span>PLAYER ID CARD</span>
                    </div>
                </div>

                <div class="bd">
                    <img class="photo" src="{{ $p->photo_url }}" alt="">

                    <div class="info">
                        <div class="nm" @if (mb_strlen($p->name) > 18) style="font-size:3mm" @endif>{{ $p->name }}</div>
                        <div class="row"><small>Date of birth</small><span>{{ $p->dob->format('d M Y') }}</span></div>
                        <div class="row"><small>Group</small><span>{{ $p->age_group ?? '-' }} &middot; {{ $p->days_text }}</span></div>
                        <div class="row"><small>Parent contact</small><span>{{ $p->guardian_phone }}</span></div>
                    </div>

                    <div class="qrbox">
                        <div class="qr">{!! \App\Support\Qr::svg($p->qr_token, 160) !!}</div>
                        <div class="no">{{ $p->player_no }}</div>
                    </div>
                </div>

                <div class="ft">
                    <span>Scan the QR code to mark attendance</span>
                    <span>If found, please return to the academy</span>
                </div>
            </div>
        @empty
            <p style="grid-column:1/-1;text-align:center;padding:40px">No players to print.</p>
        @endforelse
    </div>
</body>

</html>