@extends('layouts.admin')

@section('title', 'Dashboard Operator')
@section('page-title', 'Dashboard Operator')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('breadcrumb')
    <li class="breadcrumb-item active text-main" aria-current="page">Dashboard</li>
@endsection

@section('content')

    {{-- ==========================================
         ALERT: SESI AKTIF
         ========================================== --}}
    @if ($activeSessions->count() > 0)
        <div class="alert-custom alert-custom-success mb-4">
            <i class="bi bi-broadcast alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>{{ $activeSessions->count() }} sesi sedang aktif.</strong>
                Buka device check-in atau voting untuk memulai.
            </div>
        </div>
    @endif

    {{-- ==========================================
         STATS
         ========================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sesi Hari Ini</div>
                            <div class="fs-2 fw-bold">{{ $stats['total_sessions_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-calendar-event fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sesi Aktif</div>
                            <div class="fs-2 fw-bold text-success">{{ $stats['total_active'] }}</div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-broadcast fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Check-in Hari Ini</div>
                            <div class="fs-2 fw-bold text-info">{{ $stats['total_checkin_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-door-open-fill fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sudah Memilih</div>
                            <div class="fs-2 fw-bold text-warning">{{ $stats['total_voted_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-check2-square fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         CHART SECTION
         ========================================== --}}
    <div class="row g-3 mb-4">

        {{-- Chart 1: Partisipasi per Sesi Aktif --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <strong>
                        <i class="bi bi-bar-chart-fill text-primary me-1"></i>
                        Partisipasi Sesi Aktif
                    </strong>
                    @if ($chartData['has_active_session'])
                        <span class="badge bg-success-subtle text-success">
                            {{ $activeSessions->count() }} sesi
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($chartData['has_active_session'])
                        <div id="chartPartisipasiSesi"></div>
                    @else
                        <div class="text-center text-muted py-5">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; background: #f8f9fa;">
                                <i class="bi bi-bar-chart" style="font-size: 1.8rem; opacity: 0.4;"></i>
                            </div>
                            <p class="mt-2 mb-1 fw-medium">Tidak ada sesi aktif</p>
                            <small>Chart muncul saat ada sesi yang sedang berjalan</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Chart 2: Gauge Total Partisipasi --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-speedometer2 text-success me-1"></i>
                        Partisipasi Hari Ini
                    </strong>
                </div>
                <div class="card-body">
                    <div id="chartGaugePartisipasi"></div>

                    @if ($chartData['gauge_partisipasi']['total_voters'] > 0)
                        <div class="row text-center mt-3 pt-3 border-top">
                            <div class="col-4">
                                <div class="fw-bold text-success">
                                    {{ $chartData['gauge_partisipasi']['total_voted'] }}
                                </div>
                                <small class="text-muted">Memilih</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-secondary">
                                    {{ $chartData['gauge_partisipasi']['total_golput'] }}
                                </div>
                                <small class="text-muted">Golput</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-primary">
                                    {{ $chartData['gauge_partisipasi']['total_voters'] }}
                                </div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ==========================================
         QUICK ACTIONS
         ========================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a href="{{ route('device.checkin.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-door-open-fill fs-2 text-info"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1">Device Check-in</h5>
                                <p class="text-muted small mb-0">
                                    Buka device pintu untuk absensi siswa
                                </p>
                            </div>
                            <i class="bi bi-arrow-right fs-4 text-muted ms-auto"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('device.voting.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-box-arrow-in-right fs-2 text-success"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1">Device Voting</h5>
                                <p class="text-muted small mb-0">
                                    Buka bilik suara untuk voting
                                </p>
                            </div>
                            <i class="bi bi-arrow-right fs-4 text-muted ms-auto"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ==========================================
         SESI AKTIF SEKARANG
         ========================================== --}}
    @if ($activeSessions->count() > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <strong>
                    <i class="bi bi-broadcast text-success me-1"></i>
                    Sesi Sedang Berlangsung
                </strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kelas</th>
                                <th>Pemilihan</th>
                                <th>Waktu</th>
                                <th class="text-center">Progress</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeSessions as $session)
                                @php
                                    $total = $session->voters()->count();
                                    $checkedIn = $session->voters()->where('checked_in', true)->count();
                                    $voted = $session->voters()->where('has_voted', true)->count();
                                    $pct = $total > 0 ? round(($voted / $total) * 100) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold fs-5">
                                            {{ $session->classRoom?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $session->election->title }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td style="min-width: 180px;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <small class="text-muted" style="min-width: 60px;">
                                                Vote
                                            </small>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                                            </div>
                                            <small class="text-muted" style="min-width: 45px;">
                                                {{ $voted }}/{{ $total }}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted" style="min-width: 60px;">
                                                Check-in
                                            </small>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-info" style="width: {{ $total > 0 ? round(($checkedIn / $total) * 100) : 0 }}%;"></div>
                                            </div>
                                            <small class="text-muted" style="min-width: 45px;">
                                                {{ $checkedIn }}/{{ $total }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('device.checkin.index') }}" class="btn-custom btn-custom-sm btn-custom-outline-primary" title="Check-in">
                                                <i class="bi bi-door-open"></i>
                                            </a>
                                            <a href="{{ route('device.voting.index') }}" class="btn-custom btn-custom-sm btn-custom-outline-secondary" title="Voting">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </a>
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

    {{-- ==========================================
         JADWAL HARI INI
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-clock-history text-primary me-1"></i>
                Jadwal Sesi Hari Ini
            </strong>
            <span class="badge bg-secondary">{{ $todaySessions->count() }} sesi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kelas</th>
                            <th>Waktu</th>
                            <th>Operator</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todaySessions as $session)
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        {{ $session->classRoom?->name ?? '-' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $session->election->title }}
                                    </small>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-muted"></i>
                                    {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                    –
                                    {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                </td>
                                <td>
                                    @if ($session->operator)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person"></i>
                                            {{ $session->operator->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($session->status->value === 'active')
                                        <span class="badge-table success">Aktif</span>
                                    @elseif ($session->status->value === 'closed')
                                        <span class="badge-table failed">Selesai</span>
                                    @else
                                        <span class="badge-table pending">Terjadwal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Tidak ada sesi hari ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            @if ($chartData['has_active_session'])
                // ==========================================
                // CHART 1: Partisipasi per Sesi Aktif
                // ==========================================
                const partisipasiData = @json($chartData['partisipasi_sesi']);

                const chartPartisipasiSesi = new ApexCharts(
                    document.querySelector("#chartPartisipasiSesi"), {
                        chart: {
                            type: 'bar',
                            height: 340,
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'system-ui, -apple-system, sans-serif',
                        },
                        series: [{
                                name: 'Sudah Vote',
                                data: partisipasiData.voted_pct
                            },
                            {
                                name: 'Check-in',
                                data: partisipasiData.checked_in_pct
                            },
                        ],
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '60%',
                                dataLabels: {
                                    position: 'top'
                                },
                            },
                        },
                        colors: ['#198754', '#0dcaf0'],
                        dataLabels: {
                            enabled: true,
                            formatter: (val) => val > 0 ? val + '%' : '',
                            offsetY: -20,
                            style: {
                                fontSize: '10px',
                                fontWeight: 'bold',
                                colors: ['#1a2e1a'],
                            },
                        },
                        xaxis: {
                            categories: partisipasiData.labels,
                            labels: {
                                style: {
                                    fontSize: '11px'
                                }
                            },
                        },
                        yaxis: {
                            max: 100,
                            labels: {
                                formatter: (val) => val + '%'
                            },
                        },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            y: {
                                formatter: function(val, opts) {
                                    const idx = opts.dataPointIndex;
                                    const seriesName = opts.w.globals.seriesNames[opts.seriesIndex];
                                    const total = partisipasiData.total_voters[idx];
                                    const count = seriesName === 'Sudah Vote' ?
                                        partisipasiData.voted_count[idx] :
                                        partisipasiData.checked_in_count[idx];
                                    return `${val}% (${count}/${total})`;
                                },
                            },
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            fontSize: '12px',
                        },
                    }
                );

                chartPartisipasiSesi.render();
            @endif

            // ==========================================
            // CHART 2: Gauge Partisipasi
            // ==========================================
            const gaugeData = @json($chartData['gauge_partisipasi']);

            const chartGaugePartisipasi = new ApexCharts(
                document.querySelector("#chartGaugePartisipasi"), {
                    chart: {
                        type: 'radialBar',
                        height: 260,
                        fontFamily: 'system-ui, -apple-system, sans-serif',
                    },
                    series: [gaugeData.percentage],
                    plotOptions: {
                        radialBar: {
                            hollow: {
                                size: '65%'
                            },
                            track: {
                                background: '#e9ecef',
                                strokeWidth: '100%',
                            },
                            dataLabels: {
                                name: {
                                    show: true,
                                    fontSize: '13px',
                                    color: '#6c757d',
                                    offsetY: 22,
                                },
                                value: {
                                    show: true,
                                    fontSize: '32px',
                                    fontWeight: 'bold',
                                    color: '#1a2e1a',
                                    offsetY: -12,
                                    formatter: (val) => val + '%',
                                },
                            },
                        },
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'dark',
                            type: 'horizontal',
                            gradientToColors: [
                                gaugeData.percentage >= 75 ? '#198754' :
                                gaugeData.percentage >= 40 ? '#ffc107' : '#dc3545'
                            ],
                            stops: [0, 100],
                        },
                    },
                    stroke: {
                        lineCap: 'round'
                    },
                    labels: ['Partisipasi'],
                    colors: [
                        gaugeData.percentage >= 75 ? '#198754' :
                        gaugeData.percentage >= 40 ? '#ffc107' : '#dc3545'
                    ],
                }
            );

            chartGaugePartisipasi.render();
        });
    </script>
@endpush
