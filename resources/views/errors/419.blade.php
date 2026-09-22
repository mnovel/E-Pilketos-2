@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi Kadaluarsa')
@section('message', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan refresh halaman atau login kembali.')

@section('icon', 'bi-clock-history')
@section('icon-color', '#cc9a06')
@section('icon-bg', '#fff3cd')

@section('actions')
    <a href="javascript:location.reload()" class="btn-error btn-primary-error">
        <i class="bi bi-arrow-clockwise"></i> Refresh Halaman
    </a>
    <a href="{{ route('login') }}" class="btn-error btn-outline-error">
        <i class="bi bi-box-arrow-in-right"></i> Login Ulang
    </a>
@endsection
