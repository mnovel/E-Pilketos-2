<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kartu Voter — {{ $class->name }}</title>
    <style>
        @page {
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1a1a1a;
        }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2mm 3mm;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .card {
            border: 2px solid #1a2e1a;
            border-radius: 4mm;
            padding: 3mm;
            height: 48mm;
            box-sizing: border-box;
            background: #f9fef0;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6mm;
            background: linear-gradient(90deg, #a8d92d 0%, #c6f135 100%);
        }

        .header {
            text-align: center;
            margin-bottom: 2mm;
            padding-top: 6mm;
            border-bottom: 1px dashed #1a2e1a;
            padding-bottom: 1.5mm;
        }

        .school-name {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a2e1a;
        }

        .event-name {
            font-size: 7px;
            color: #666;
            margin-top: 0.5mm;
        }

        .body-content {
            display: table;
            width: 100%;
        }

        .info-section {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
            padding-right: 2mm;
        }

        .qr-section {
            display: table-cell;
            width: 40%;
            text-align: center;
            vertical-align: middle;
        }

        .qr-section img {
            width: 22mm;
            height: 22mm;
        }

        .field-label {
            font-size: 6.5px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .field-value {
            font-size: 10px;
            font-weight: bold;
            color: #1a2e1a;
            margin-bottom: 1mm;
            word-wrap: break-word;
        }

        .field-value.small {
            font-size: 8px;
        }

        .token {
            font-family: 'Courier New', monospace;
            font-size: 6px;
            color: #888;
            margin-top: 0.5mm;
            word-break: break-all;
        }

        .footer {
            position: absolute;
            bottom: 1mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 6px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>

    <table class="grid">
        @foreach ($cards->chunk(2) as $chunk)
            <tr>
                @foreach ($chunk as $card)
                    <td>
                        <div class="card">
                            <div class="header">
                                <div class="school-name">{{ $school }}</div>
                                <div class="event-name">Kartu Pemilih — Pemilihan Ketua OSIS 2026</div>
                            </div>

                            <div class="body-content">
                                <div class="info-section">
                                    <div class="field-label">Nama</div>
                                    <div class="field-value">{{ Str::limit($card['nama'], 28) }}</div>

                                    <div class="field-label">NIS</div>
                                    <div class="field-value small">{{ $card['nis'] }}</div>

                                    <div class="field-label">Kelas</div>
                                    <div class="field-value small">{{ $card['kelas'] }}</div>
                                </div>

                                <div class="qr-section">
                                    <img src="data:image/png;base64,{{ $card['qr'] }}" alt="QR">
                                    <div class="token">{{ $card['token'] }}</div>
                                </div>
                            </div>

                            <div class="footer">
                                Simpan kartu ini — bawa saat pemungutan suara
                            </div>
                        </div>
                    </td>
                @endforeach

                @if ($chunk->count() < 2)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>

</body>

</html>
