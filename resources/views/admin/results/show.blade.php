@extends('layouts.admin')

@section('title', 'Hasil Pemilihan')
@section('page-title', 'Hasil Pemilihan')
@section('page-subtitle', $election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.show', $election) }}" class="text-decoration-none text-muted-green">
            {{ $election->title }}
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Hasil</li>
@endsection

@section('content')

    @php
        $runtime = $election->getRuntimeStatus();
        $isFinal = in_array($runtime, ['closed', 'published']);

        $statusMap = [
            'active' => ['class' => 'bg-warning text-dark', 'icon' => 'hourglass-split', 'label' => 'Hasil Sementara — Voting Masih Berlangsung'],
            'closed' => ['class' => 'bg-secondary', 'icon' => 'stop-circle-fill', 'label' => 'Hasil Final — Belum Dipublikasi'],
            'published' => ['class' => 'bg-success', 'icon' => 'megaphone-fill', 'label' => 'Hasil Final — Sudah Dipublikasi'],
        ];
        $status = $statusMap[$runtime] ?? null;
    @endphp

    {{-- ==========================================
         TOOLBAR: BACK + EXPORT + STATUS
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.elections.show', $election) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="d-flex gap-2 flex-wrap align-items-center">

            {{-- Export PDF --}}
            <a href="{{ route('admin.elections.export-pdf', $election) }}" class="btn-custom btn-custom-outline-danger">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>

            {{-- Export Excel --}}
            <a href="{{ route('admin.elections.export-excel', $election) }}" class="btn-custom btn-custom-outline-primary">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>

            {{-- Status badge --}}
            @if ($status)
                <span class="badge {{ $status['class'] }} py-2 px-3">
                    <i class="bi bi-{{ $status['icon'] }}"></i> {{ $status['label'] }}
                </span>
            @endif

        </div>
    </div>

    {{-- ==========================================
         INFO ALERT
         ========================================== --}}
    @if ($runtime === 'active')
        <div class="alert-custom alert-custom-info mb-4">
            <i class="bi bi-info-circle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Hasil sementara.</strong>
                Data ini masih berubah selama voting berlangsung.
                Hasil final ditampilkan setelah pemilihan ditutup.
            </div>
        </div>
    @endif

    {{-- ==========================================
         STATS RINGKASAN
         ========================================== --}}
    <div class="row g-3 mb-4">

        @php
            $cards = [
                [
                    'label' => 'Suara Sah',
                    'value' => $stats['total_voted'],
                    'icon' => 'check2-square',
                    'bg' => '#d1e7dd',
                    'color' => 'text-success',
                ],
                [
                    'label' => 'Total Pemilih',
                    'value' => $stats['total_voters'],
                    'icon' => 'people-fill',
                    'bg' => '#cfe2ff',
                    'color' => 'text-primary',
                ],
                [
                    'label' => 'Golput',
                    'value' => $stats['total_golput'],
                    'icon' => 'person-dash',
                    'bg' => '#fff3cd',
                    'color' => 'text-warning',
                ],
                [
                    'label' => 'Partisipasi',
                    'value' => $stats['participation_pct'] . '%',
                    'icon' => 'graph-up-arrow',
                    'bg' => '#cff4fc',
                    'color' => 'text-info',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: {{ $card['bg'] }};">
                            <i class="bi bi-{{ $card['icon'] }} fs-4 {{ $card['color'] }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                            <div class="fs-3 fw-bold {{ $card['color'] }}">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- ==========================================
         GRAFIK PEROLEHAN SUARA
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <strong>
                <i class="bi bi-bar-chart-fill text-success me-1"></i>
                Perolehan Suara
            </strong>
        </div>
        <div class="card-body">
            @if (count($candidates) > 0)
                <div id="chartPerolehan" style="min-height: 350px;"></div>
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bar-chart" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="mt-2 mb-0">Belum ada kandidat</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ==========================================
         DETAIL KANDIDAT
         ========================================== --}}
    @if (count($candidates) > 0)
        <div class="mb-2">
            <h6 class="text-muted small mb-2 text-uppercase">
                <i class="bi bi-person-badge me-1"></i> Detail Kandidat
            </h6>
        </div>

        <div class="row g-3 mb-4">
            @foreach ($candidates as $c)
                @php
                    $isWinner = $c['is_winner'] && $isFinal;
                @endphp

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 {{ $isWinner ? 'border-success' : '' }}" style="{{ $isWinner ? 'border-width: 2px !important; border-style: solid !important;' : '' }}">

                        {{-- Winner badge --}}
                        @if ($isWinner)
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-success">
                                    <i class="bi bi-trophy-fill"></i> Pemenang
                                </span>
                            </div>
                        @endif

                        <div class="card-body text-center pt-4">

                            {{-- Photo --}}
                            @if ($c['foto'])
                                <img src="{{ $c['foto'] }}" alt="{{ $c['nama'] }}" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover; border: 4px solid #c6f135;">
                            @else
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 100px; height: 100px; background: #c6f135;
                                            color: #1a2e1a; font-weight: 700; font-size: 2.2rem;
                                            border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    {{ strtoupper(substr($c['nama'], 0, 1)) }}
                                </div>
                            @endif

                            {{-- No urut --}}
                            <div class="mb-2">
                                <span class="badge" style="background: #1a2e1a; color: #c6f135;
                                             font-size: 0.85rem; padding: 6px 12px;">
                                    No. {{ $c['no_urut'] }}
                                </span>
                            </div>

                            <h5 class="mb-1">{{ $c['nama'] }}</h5>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-mortarboard"></i> {{ $c['kelas'] }}
                            </p>

                            {{-- Votes --}}
                            <div class="mb-2">
                                <div class="fs-2 fw-bold text-success">{{ $c['votes'] }}</div>
                                <small class="text-muted">suara</small>
                            </div>

                            {{-- Progress --}}
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar {{ $isWinner ? 'bg-success' : 'bg-secondary' }}" style="width: {{ $c['percentage'] }}%;"></div>
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
    @endif

    {{-- ==========================================
         REKAP PARTISIPASI PER KELAS
         ========================================== --}}
    @if (count($sessions) > 0)
        <div class="mb-2">
            <h6 class="text-muted small mb-2 text-uppercase">
                <i class="bi bi-people me-1"></i> Rekap Per Kelas
            </h6>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kelas</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Check-in</th>
                                <th class="text-center">Memilih</th>
                                <th class="text-center">Golput</th>
                                <th style="min-width: 180px;">Partisipasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $s)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $s['kelas'] }}</div>
                                        <small class="text-muted">{{ $s['status_label'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-medium">{{ $s['total_voters'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-info fw-medium">{{ $s['checked_in'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success fw-medium">{{ $s['voted'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ $s['golput'] > 0 ? 'text-warning' : 'text-muted' }} fw-medium">
                                            {{ $s['golput'] }}
                                        </span>
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
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-center">{{ $stats['total_voters'] }}</th>
                                <th class="text-center">—</th>
                                <th class="text-center text-success">{{ $stats['total_voted'] }}</th>
                                <th class="text-center text-warning">{{ $stats['total_golput'] }}</th>
                                <th>
                                    <span class="fw-bold">{{ $stats['participation_pct'] }}%</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script src="{{ asset('storage/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        @if (count($candidates) > 0)
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
                            position: 'top'
                        }
                    }
                },
                colors: candidates.map(c => c.is_winner ? '#198754' : '#a8d92d'),
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    style: {
                        fontSize: '14px',
                        fontWeight: 'bold',
                        colors: ['#1a2e1a']
                    },
                    formatter: val => val + ' suara'
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
                        formatter: val => Math.floor(val)
                    }
                },
                tooltip: {
                    y: {
                        formatter: (val, opts) => {
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
        @endif
    </script>
@endpush
