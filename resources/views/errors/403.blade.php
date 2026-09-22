@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses Ditolak')
@section('message', 'Anda tidak memiliki izin untuk mengakses halaman ini. Pastikan Anda login dengan akun yang sesuai.')

@section('icon', 'bi-shield-lock-fill')
@section('icon-color', '#dc3545')
@section('icon-bg', '#f8d7da')

@section('actions')
    <a href="{{ url()->previous() }}" class="btn-error btn-primary-error">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <a href="{{ url('/') }}" class="btn-error btn-outline-error">
        <i class="bi bi-house"></i> Halaman Utama
    </a>
@endsection
