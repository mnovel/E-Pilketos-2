@extends('layouts.admin')

@section('title', 'Hasil Import')
@section('page-title', 'Hasil Import')
@section('page-subtitle', 'Laporan hasil import voter')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.import.index') }}" class="text-decoration-none text-muted-green">
            Import
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Report</li>
@endsection

@section('content')

    {{-- HEADER SUCCESS --}}
    <div class="card border-0 shadow-sm mb-4 text-center">
        <div class="card-body py-5">

            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #d1e7dd;">
                <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #198754;"></i>
            </div>

            <h3 class="fw-bold mb-2">Import Selesai</h3>
            <p class="text-muted mb-4">Proses import data voter sudah selesai</p>

            {{-- STATS --}}
            <div class="row g-3 justify-content-center">
                <div class="col-md-4">
                    <div class="card border-0" style="background: #f8f9fa;">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-1">Berhasil</div>
                            <div class="fs-2 fw-bold text-success">{{ $result['imported'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0" style="background: #f8f9fa;">
                        <div class="card-body text-center">
                            <div class="text-muted small mb-1">Gagal</div>
                            <div class="fs-2 fw-bold text-danger">{{ $result['failed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ERRORS --}}
    @if (!empty($result['errors']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <strong>
                    <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                    Error ({{ count($result['errors']) }})
                </strong>
            </div>
            <div class="card-body">
                <ul class="mb-0 small text-danger">
                    @foreach ($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ACTIONS --}}
    <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="{{ route('admin.voters.import.index') }}" class="btn-custom btn-custom-outline-primary">
            <i class="bi bi-cloud-upload"></i> Import Lagi
        </a>
        <a href="{{ route('admin.voters.index', ['status' => 'pending']) }}" class="btn-custom btn-custom-primary">
            <i class="bi bi-people"></i> Lihat Data Pemilih
        </a>
    </div>

@endsection
