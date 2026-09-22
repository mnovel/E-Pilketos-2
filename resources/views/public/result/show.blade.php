<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil {{ $election->title }} - Pilketos</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>

<body style="background: #f5f7fa; min-height: 100vh;">

    {{-- HEADER --}}
    <header style="background: #1a2e1a; color: white; padding: 20px 0;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="{{ route('hasil.index') }}" class="text-decoration-none">
                    <h3 class="mb-0" style="color: #c6f135;">
                        <i class="bi bi-asterisk"></i> Pilketos
                    </h3>
                </a>

                <a href="{{ route('hasil.index') }}" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Semua Hasil
                </a>
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <div style="background: linear-gradient(135deg, #c6f135 0%, #a8d92d 100%); padding: 40px 0;">
        <div class="container text-center" style="color: #1a2e1a;">
            <i class="bi bi-trophy-fill" style="font-size: 3rem;"></i>
            <h1 class="fw-bold mt-3 mb-2">{{ $election->title }}</h1>
            <p class="mb-0" style="opacity: 0.8;">
                {{ $election->tahun_ajaran }} —
                Dipublikasi {{ $election->hasil_published_at?->translatedFormat('d M Y, H:i') }}
            </p>
        </div>
    </div>

    <div class="container py-5">

        {{-- STATS --}}
        <div class="row g-3 mb-5">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Total Suara</div>
                        <div class="fs-2 fw-bold text-success">{{ $stats['total_voted'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Total Pemilih</div>
                        <div class="fs-2 fw-bold text-primary">{{ $stats['total_voters'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Golput</div>
                        <div class="fs-2 fw-bold text-warning">{{ $stats['total_golput'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Partisipasi</div>
                        <div class="fs-2 fw-bold text-info">{{ $stats['participation_pct'] }}%</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart-fill text-success me-1"></i>
                    Perolehan Suara
                </h5>
            </div>
            <div class="card-body">
                <div id="chartPerolehan" style="min-height: 350px;"></div>
            </div>
        </div>

        {{-- CANDIDATES --}}
        <h5 class="mb-3">
            <i class="bi bi-person-badge text-success me-1"></i>
            Detail Kandidat
        </h5>

        <div class="row g-4 mb-5">
            @foreach ($candidates as $c)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100
                                {{ $c['is_winner'] ? 'border-success' : '' }}"
                        style="{{ $c['is_winner'] ? 'border: 2px solid #198754 !important;' : '' }}">

                        @if ($c['is_winner'])
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-success">
                                    <i class="bi bi-trophy-fill"></i> Pemenang
                                </span>
                            </div>
                        @endif

                        <div class="card-body text-center pt-4">
                            @if ($c['foto'])
                                <img src="{{ $c['foto'] }}" class="rounded-circle mb-3"
                                    style="width: 100px; height: 100px; object-fit: cover;
                                            border: 4px solid #c6f135;">
                            @else
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 100px; height: 100px; background: #c6f135;
                                            color: #1a2e1a; font-weight: 700; font-size: 2.2rem;">
                                    {{ strtoupper(substr($c['nama'], 0, 1)) }}
                                </div>
                            @endif

                            <div class="mb-2">
                                <span class="badge" style="background: #1a2e1a; color: #c6f135;
                                             padding: 6px 12px;">
                                    No. {{ $c['no_urut'] }}
                                </span>
                            </div>

                            <h5 class="mb-1">{{ $c['nama'] }}</h5>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-mortarboard"></i> {{ $c['kelas'] }}
                            </p>

                            <div class="mb-2">
                                <div class="fs-2 fw-bold text-success">{{ $c['votes'] }}</div>
                                <small class="text-muted">suara</small>
                            </div>

                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar
                                            {{ $c['is_winner'] ? 'bg-success' : 'bg-secondary' }}" style="width: {{ $c['percentage'] }}%;">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between small text-muted">
                                <span>{{ $c['percentage'] }}%</span>
                                <span>Rank #{{ $c['rank'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- REKAP PER KELAS --}}
        @if (count($sessions) > 0)
            <h5 class="mb-3">
                <i class="bi bi-people text-success me-1"></i>
                Rekap Per Kelas
            </h5>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kelas</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Memilih</th>
                                    <th class="text-center">Golput</th>
                                    <th style="min-width: 180px;">Partisipasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sessions as $s)
                                    <tr>
                                        <td class="fw-medium">{{ $s['kelas'] }}</td>
                                        <td class="text-center">{{ $s['total_voters'] }}</td>
                                        <td class="text-center text-success fw-medium">{{ $s['voted'] }}</td>
                                        <td class="text-center {{ $s['golput'] > 0 ? 'text-warning' : 'text-muted' }} fw-medium">
                                            {{ $s['golput'] }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $s['participation'] }}%;"></div>
                                                </div>
                                                <small class="text-muted" style="min-width: 50px;">
                                                    {{ $s['participation'] }}%
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- FOOTER --}}
    <footer class="text-center py-4 text-muted small">
        &copy; {{ date('Y') }} Pilketos Digital
    </footer>

    {{-- CHART SCRIPT --}}
    <script>
        const candidates = @json($candidates);

        const options = {
            series: [{
                name: 'Perolehan Suara',
                data: candidates.map(c => c.votes)
            }],
            chart: {
                type: 'bar',
                height: 380,
                toolbar: {
                    show: false
                },
                fontFamily: 'system-ui, sans-serif',
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '50%',
                    distributed: true,
                    dataLabels: {
                        position: 'top',
                    }
                }
            },
            colors: candidates.map(c =>
                c.is_winner ? '#198754' : '#a8d92d'
            ),
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    colors: ['#1a2e1a']
                },
                formatter: function(val) {
                    return val + ' suara';
                }
            },
            xaxis: {
                categories: candidates.map(c => `#${c.no_urut} ${c.nama}`),
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Jumlah Suara',
                    style: {
                        fontWeight: 'bold'
                    }
                },
                labels: {
                    formatter: function(val) {
                        return Math.floor(val);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val, opts) {
                        const c = candidates[opts.dataPointIndex];
                        return `${val} suara (${c.percentage}%)`;
                    }
                }
            },
            legend: {
                show: false
            },
        };

        const chart = new ApexCharts(
            document.querySelector('#chartPerolehan'),
            options
        );
        chart.render();
    </script>

</body>

</html>
