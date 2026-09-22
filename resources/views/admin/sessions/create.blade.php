@extends('layouts.admin')

@section('title', 'Buat Sesi')
@section('page-title', 'Buat Sesi Voting')
@section('page-subtitle', $election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sessions.index', ['election_id' => $election->id]) }}" class="text-decoration-none text-muted-green">
            Sesi Voting
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Buat</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.css') }}">
@endpush

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.sessions.index', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- INFO: Range Election --}}
    <div class="alert-custom alert-custom-info mb-3">
        <i class="bi bi-info-circle-fill alert-custom-icon"></i>
        <div class="alert-custom-content">
            <strong>Rentang pemilihan:</strong>
            {{ $election->start_at->translatedFormat('d M Y, H:i') }} –
            {{ $election->end_at->translatedFormat('d M Y, H:i') }}
            <br>
            <small>Tanggal sesi harus dalam rentang ini.</small>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-clock-history text-success me-1"></i> Form Sesi Baru</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.sessions.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="election_id" value="{{ $election->id }}">

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required autofocus>
                                <option value="">— Pilih Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Kelas yang sudah punya sesi tidak muncul di sini.
                            </small>
                        </div>

                        {{-- Tanggal --}}
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">
                                Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="tanggal" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal') }}" placeholder="Pilih tanggal"
                                required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Waktu --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="waktu_mulai" class="form-label">
                                    Waktu Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="waktu_mulai" name="waktu_mulai" class="form-control @error('waktu_mulai') is-invalid @enderror" value="{{ old('waktu_mulai') }}"
                                    placeholder="08:00" required>
                                @error('waktu_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="waktu_selesai" class="form-label">
                                    Waktu Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="waktu_selesai" name="waktu_selesai" class="form-control @error('waktu_selesai') is-invalid @enderror" value="{{ old('waktu_selesai') }}"
                                    placeholder="08:30" required>
                                @error('waktu_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Operator --}}
                        <div class="mb-3">
                            <label for="operator_id" class="form-label">Operator</label>
                            <select id="operator_id" name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                                <option value="">— Pilih Operator (opsional) —</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" {{ old('operator_id') == $op->id ? 'selected' : '' }}>
                                        {{ $op->name }} ({{ $op->role->value ?? $op->role }})
                                    </option>
                                @endforeach
                            </select>
                            @error('operator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Auto Assign --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="auto_assign" name="auto_assign" value="1" class="form-check-input" {{ old('auto_assign', true) ? 'checked' : '' }}>
                                <label for="auto_assign" class="form-check-label">
                                    Otomatis assign pemilih dari kelas ini
                                </label>
                            </div>
                            <small class="text-muted d-block ms-4">
                                Sistem akan mencari semua pemilih terverifikasi dengan kelas yang sama.
                            </small>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.sessions.index', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Sesi
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        flatpickr('#tanggal', {
            dateFormat: 'Y-m-d',
            minDate: '{{ $election->start_at->format('Y-m-d') }}',
            maxDate: '{{ $election->end_at->format('Y-m-d') }}',
        });

        flatpickr('#waktu_mulai', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            defaultDate: '08:00',
        });

        flatpickr('#waktu_selesai', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            defaultDate: '08:30',
        });
    </script>
@endpush
