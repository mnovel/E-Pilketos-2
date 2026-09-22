@extends('layouts.admin')

@section('title', 'Edit Sesi')
@section('page-title', 'Edit Sesi')
@section('page-subtitle', ($session->classRoom?->name ?? '-') . ' — ' . $session->election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sessions.index', ['election_id' => $session->election_id]) }}" class="text-decoration-none text-muted-green">
            Sesi Voting
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Edit</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.css') }}">
@endpush

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.sessions.show', $session) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-pencil text-warning me-1"></i> Edit Sesi</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.sessions.update', $session) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required autofocus>
                                <option value="">— Pilih Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id', $session->class_id) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tanggal --}}
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">
                                Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="tanggal" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', $session->tanggal->format('Y-m-d')) }}"
                                required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Harus dalam rentang pemilihan:
                                {{ $session->election->start_at->translatedFormat('d M Y') }} –
                                {{ $session->election->end_at->translatedFormat('d M Y') }}
                            </small>
                        </div>

                        {{-- Waktu --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="waktu_mulai" class="form-label">
                                    Waktu Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="waktu_mulai" name="waktu_mulai" class="form-control @error('waktu_mulai') is-invalid @enderror"
                                    value="{{ old('waktu_mulai', \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i')) }}" required>
                                @error('waktu_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="waktu_selesai" class="form-label">
                                    Waktu Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="waktu_selesai" name="waktu_selesai" class="form-control @error('waktu_selesai') is-invalid @enderror"
                                    value="{{ old('waktu_selesai', \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i')) }}" required>
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
                                    <option value="{{ $op->id }}" {{ old('operator_id', $session->operator_id) == $op->id ? 'selected' : '' }}>
                                        {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.sessions.show', $session) }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Perubahan
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
            minDate: '{{ $session->election->start_at->format('Y-m-d') }}',
            maxDate: '{{ $session->election->end_at->format('Y-m-d') }}',
        });

        flatpickr('#waktu_mulai', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
        });

        flatpickr('#waktu_selesai', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
        });
    </script>
@endpush
