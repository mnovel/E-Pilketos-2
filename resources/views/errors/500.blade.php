@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi Kesalahan Server')
@section('message', 'Maaf, sistem mengalami gangguan internal. Tim kami sudah menerima notifikasi. Silakan coba beberapa saat lagi.')

@section('icon', 'bi-bug-fill')
@section('icon-color', '#dc3545')
@section('icon-bg', '#f8d7da')

@section('actions')
    <a href="javascript:location.reload()" class="btn-error btn-primary-error">
        <i class="bi bi-arrow-clockwise"></i> Refresh Halaman
    </a>
    <a href="{{ url('/') }}" class="btn-error btn-outline-error">
        <i class="bi bi-house"></i> Halaman Utama
    </a>
@endsection
