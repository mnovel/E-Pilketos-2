@extends('layouts.admin')

@section('title', 'Preview Import')
@section('page-title', 'Preview Import')
@section('page-subtitle', 'Validasi data sebelum import')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.import.index') }}" class="text-decoration-none text-muted-green">
            Import
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Preview</li>
@endsection

@section('content')

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-list-ol fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Baris</div>
                        <div class="fs-3 fw-bold">{{ count($preview) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Valid</div>
                        <div class="fs-3 fw-bold text-success">{{ $valid }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #f8d7da;">
                        <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Invalid</div>
                        <div class="fs-3 fw-bold text-danger">{{ $invalid }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERT --}}
    @if ($invalid > 0)
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>{{ $invalid }} baris akan di-skip.</strong>
                Baris dengan error akan dilewati. Hanya {{ $valid }} baris valid yang diimport.
            </div>
        </div>
    @endif

    {{-- TABLE --}}
    <div class="table-card-custom mb-4">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 60px;">Baris</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Email</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview as $item)
                        <tr class="{{ $item['valid'] ? '' : 'table-danger' }}">
                            <td class="text-muted">#{{ $item['row'] }}</td>
                            <td><code>{{ $item['data']['nis'] ?: '—' }}</code></td>
                            <td>{{ $item['data']['nama'] ?: '—' }}</td>
                            <td>
                                @if ($item['data']['kelas'])
                                    <span class="badge bg-light text-dark border">
                                        {{ $item['data']['kelas'] }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $item['data']['email'] ?: '(auto)' }}</small>
                            </td>
                            <td class="text-center">
                                @if ($item['valid'])
                                    <span class="badge-table success">
                                        <i class="bi bi-check-circle"></i> OK
                                    </span>
                                @else
                                    <div class="badge-table failed">
                                        <i class="bi bi-x-circle"></i> Error
                                    </div>
                                    <div class="small text-danger mt-1">
                                        @foreach ($item['errors'] as $error)
                                            <div>• {{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ACTIONS --}}
    <div class="d-flex justify-content-between gap-2 mb-4">
        <a href="{{ route('admin.voters.import.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Upload Ulang
        </a>

        <form action="{{ route('admin.voters.import.import') }}" method="POST">
            @csrf
            <button type="submit" class="btn-custom btn-custom-primary" onclick="return confirm('Import {{ $valid }} voter valid?')">
                <i class="bi bi-cloud-upload"></i> Import {{ $valid }} Voter
            </button>
        </form>
    </div>

@endsection
