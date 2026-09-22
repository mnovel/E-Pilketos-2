<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil {{ $election->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a2e1a;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #1a2e1a;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #666;
        }

        .info-box {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-box td {
            padding: 3px 5px;
        }

        .info-box td:first-child {
            width: 30%;
            color: #666;
        }

        .stats {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .stats td {
            width: 25%;
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .stats .label {
            font-size: 10px;
            color: #666;
            margin-bottom: 4px;
        }

        .stats .value {
            font-size: 20px;
            font-weight: bold;
            color: #1a2e1a;
        }

        h2 {
            font-size: 14px;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #198754;
            color: #198754;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.data th {
            background: #1a2e1a;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        table.data td {
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
        }

        table.data tr.winner {
            background: #d1e7dd;
            font-weight: bold;
        }

        table.data .text-center {
            text-align: center;
        }

        table.data .text-right {
            text-align: right;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            width: 100px;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-bar .fill {
            height: 100%;
            background: #198754;
        }

        .badge-winner {
            display: inline-block;
            padding: 2px 6px;
            background: #198754;
            color: white;
            font-size: 9px;
            border-radius: 3px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>HASIL {{ strtoupper($election->title) }}</h1>
        <div class="subtitle">
            Tahun Ajaran {{ $election->tahun_ajaran }}
        </div>
    </div>

    {{-- INFO --}}
    <div class="info-box">
        <table>
            <tr>
                <td>Periode Pemilihan</td>
                <td><strong>{{ $election->start_at->translatedFormat('d M Y H:i') }} – {{ $election->end_at->translatedFormat('d M Y H:i') }}</strong></td>
            </tr>
            <tr>
                <td>Dipublikasi</td>
                <td>{{ $election->hasil_published_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Dicetak</td>
                <td>{{ now()->translatedFormat('d M Y, H:i') }}</td>
            </tr>
        </table>
    </div>

    {{-- STATS --}}
    <table class="stats">
        <tr>
            <td>
                <div class="label">Total Suara</div>
                <div class="value">{{ $stats['total_voted'] }}</div>
            </td>
            <td>
                <div class="label">Total Pemilih</div>
                <div class="value">{{ $stats['total_voters'] }}</div>
            </td>
            <td>
                <div class="label">Golput</div>
                <div class="value">{{ $stats['total_golput'] }}</div>
            </td>
            <td>
                <div class="label">Partisipasi</div>
                <div class="value">{{ $stats['participation_pct'] }}%</div>
            </td>
        </tr>
    </table>

    {{-- PEROLEHAN SUARA --}}
    <h2>Perolehan Suara</h2>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 50px;">Rank</th>
                <th style="width: 60px;">No Urut</th>
                <th>Nama Kandidat</th>
                <th>Kelas</th>
                <th class="text-right">Suara</th>
                <th class="text-right">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($candidates as $c)
                <tr class="{{ $c['is_winner'] ? 'winner' : '' }}">
                    <td>
                        #{{ $c['rank'] }}
                        @if ($c['is_winner'])
                            <span class="badge-winner">🏆</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $c['no_urut'] }}</td>
                    <td>{{ $c['nama'] }}</td>
                    <td>{{ $c['kelas'] }}</td>
                    <td class="text-right">{{ $c['votes'] }}</td>
                    <td class="text-right">{{ $c['percentage'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- REKAP PER KELAS --}}
    @if (count($sessions) > 0)
        <h2>Rekap Per Kelas</h2>

        <table class="data">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Memilih</th>
                    <th class="text-center">Golput</th>
                    <th class="text-center">Partisipasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $s)
                    <tr>
                        <td>{{ $s['kelas'] }}</td>
                        <td class="text-center">{{ $s['total_voters'] }}</td>
                        <td class="text-center">{{ $s['voted'] }}</td>
                        <td class="text-center">{{ $s['golput'] }}</td>
                        <td class="text-center">
                            <div class="progress-bar">
                                <div class="fill" style="width: {{ $s['participation'] }}%;"></div>
                            </div>
                            {{ $s['participation'] }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        Dokumen ini dibuat otomatis oleh sistem Pilketos Digital<br>
        &copy; {{ date('Y') }} — {{ config('app.name') }}
    </div>

</body>

</html>
