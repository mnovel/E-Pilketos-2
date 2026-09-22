@extends('layouts.admin')

@section('title', 'Detail Activity Log')
@section('page-title', 'Detail Activity Log')
@section('page-subtitle', 'Log #' . $log->id)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.activity-logs.index') }}" class="text-decoration-none text-muted-green">
            Activity Log
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">#{{ $log->id }}</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.activity-logs.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @php
        $color = $log->getActionColor();
        $icon = $log->getActionIcon();
    @endphp

    <div class="row g-4">

        {{-- CARD UTAMA --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi {{ $icon }} text-{{ $color }} me-1"></i>
                        {{ $log->getActionLabel() }}
                    </strong>
                </div>
                <div class="card-body p-0">

                    <table class="table table-borderless mb-0 align-middle">
                        <tbody>
                            <tr>
                                <td class="text-muted py-3 ps-4" style="width: 35%;">
                                    <i class="bi bi-hash me-1"></i> Log ID
                                </td>
                                <td class="py-3 pe-4"><code>{{ $log->id }}</code></td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-tag me-1"></i> Action
                                </td>
                                <td class="py-3 pe-4">
                                    <code class="fs-6">{{ $log->action }}</code>
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-person me-1"></i> User
                                </td>
                                <td class="py-3 pe-4">
                                    @if ($log->user)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 36px; height: 36px; background: #c6f135;
                                                        color: #1a2e1a; font-weight: 700;">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $log->user->name }}</div>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">System</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-clock me-1"></i> Waktu
                                </td>
                                <td class="py-3 pe-4">
                                    {{ $log->created_at->translatedFormat('d M Y, H:i:s') }}
                                    <small class="text-muted d-block">
                                        {{ $log->created_at->diffForHumans() }}
                                    </small>
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-globe me-1"></i> IP Address
                                </td>
                                <td class="py-3 pe-4">
                                    <code>{{ $log->ip_address ?? '—' }}</code>
                                </td>
                            </tr>
                            @if ($log->subject_type)
                                <tr class="border-top">
                                    <td class="text-muted py-3 ps-4">
                                        <i class="bi bi-link me-1"></i> Subject
                                    </td>
                                    <td class="py-3 pe-4">
                                        <code class="small">
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        </code>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                </div>
            </div>

            {{-- META DATA --}}
            @if ($log->meta)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom">
                        <strong>
                            <i class="bi bi-code-square text-info me-1"></i>
                            Metadata
                        </strong>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0"
                            style="background: #f8f9fa; padding: 15px;
                                                  border-radius: 6px;
                                                  font-size: 0.85rem;
                                                  overflow-x: auto;">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- CARD USER AGENT --}}
        <div class="col-md-4">
            @if ($log->user_agent)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <strong>
                            <i class="bi bi-browser-chrome text-warning me-1"></i>
                            User Agent
                        </strong>
                    </div>
                    <div class="card-body">
                        <p class="small mb-0" style="word-break: break-all;">
                            {{ $log->user_agent }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection
